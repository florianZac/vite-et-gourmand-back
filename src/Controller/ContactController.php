<?php
namespace App\Controller;
use App\Service\MailerService;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Target;
/**
 * @author      Florian Aizac
 * @created     23/02/2026
 * @description Contrôleur gérant l'envoi de messages de contact
 * 
 *  1. contact                  : Récupere les données de contact d'un formulaire
 *  2. validationDataContact    : Teste la validation de tous les champs du formulaire de contact
 *  3. sanitizer                : Nettoie une valeur passé en parametre selon son type pour éviter les injections
 *  4. validerEmail             : Vérifie que l'email correspond au regex
 *  5. validerSujet             : Vérifie que le sujet correspond au regex
 *  6. validerMessage           : Vérifie que le contenu du message est correct
 *  7. verifierSecurite         : Vérifie toutes les protections de sécurité avant de traiter la requête 
 */

#[Route('/api')]
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    /**
     * @description : Récupere les données de contact d'un formulaire
     * Reçoit un JSON avec le sujet, email et le message, valide et envoie un email à l'administrateur du site via le service MailerService
    */
    public function contact(
            Request $request, 
            MailerService $mailerService, 
            #[Target('contact_limiter.limiter')] RateLimiterFactory $contactLimiter
        ): JsonResponse
    {
        // Étape 1 - Récupère et décode les données JSON envoyées par le client
        $data = json_decode($request->getContent(), true);

        // Étape 2 - Vérifie toutes les protections de sécurité
        // Si la fonction retourne une JsonResponse c'est qu'une protection a été déclenchée
        $securite = $this->verifierSecurite($request, $contactLimiter, $data);
        if ($securite !== null) {
            return $securite;
        }

        // Étape 3 - Vérifie que les données sont valides
        if (!$data) {
            // Si le JSON est invalide, retourne une erreur avec un code 400
            return $this->json(['status' => 'error', 'message' => 'Format JSON invalide'], 400);
        }

        // Étape 4 - Appel de la fonction de validation qui retourne un tableau d'erreurs
        $erreurs = $this->validationDataContact($data);

        // Étape 5 - Si des erreurs existent on les retourne toutes au front avec un code 400
        if (!empty($erreurs)) {
            return $this->json(['status' => 'error', 'erreurs' => $erreurs], 400);
        }

        // Étape 6 - Tout est valide, on envoie l'email via le service
        $mailerService->sendContactEmail($data);

        // Étape 7 - Retourne une réponse de succès avec un code 200
        return $this->json(['status' => 'success', 'message' => 'Message envoyé avec succès'], 200);
    }

    /**
     * @description Teste la validation de tous les champs du formulaire de contact
     * @param array $data Les données à valider
     * @return array Tableau contenant les erreurs de validation, vide si tout est valide
     */
    private function validationDataContact(array $data): array
    {
        // Étape 1 - Création du tableau de stokage des erreurs de validation
        $erreurs = [];

        // Étape 2 - Nettoyage des données avant validation
        // On vérifie que chaque champ existe avant de le sanitizer
        // Si le champ n'existe pas on assigne une chaîne vide
        if (isset($data['email'])) {
            $email = $this->sanitizer($data['email'], 'email');
        } else {
            $email = '';
        }

        if (isset($data['sujet'])) {
            $sujet = $this->sanitizer($data['sujet'], 'texte');
        } else {
            $sujet = '';
        }

        if (isset($data['message'])) {
            $message = $this->sanitizer($data['message'], 'message');
        } else {
            $message = '';
        }

        // Étape 3 - Validation de l'email
        // On appelle la fonction validerEmail avec l'email nettoyé
        // Si elle retourne une erreur on l'ajoute au tableau $erreurs
        $erreurEmail = $this->validerEmail($email);
        if ($erreurEmail) {
            $erreurs['email'] = $erreurEmail;
        }

        // Étape 4 - Validation du sujet
        $erreurSujet = $this->validerSujet($sujet);
        if ($erreurSujet) {
            $erreurs['sujet'] = $erreurSujet;
        }

        // Étape 5 - Validation du message
        $erreurMessage = $this->validerMessage($message);
        if ($erreurMessage) {
            $erreurs['message'] = $erreurMessage;
        }

        // Étape 6 - Retourne le tableau d'erreurs, vide si tout est valide
        return $erreurs;
    }
        
    /**
     * @description Nettoie une valeur passé en parametre selon son type pour éviter les injections
     * @param string $valeur La valeur à nettoyer
     * @param string $type Le type de champ (email, texte, message)
     * @return string La valeur nettoyée
     */
    private function sanitizer(string $valeur, string $type): string
    {
        // Étape 1 - Supprime les espaces inutiles en début et fin de chaîne
        $valeur = trim($valeur);

        // Étape 2 - Supprime toutes les balises HTML et PHP
        $valeur = strip_tags($valeur);

        // Étape 3 - Convertit les caractères spéciaux HTML en entités
        $valeur = htmlspecialchars($valeur, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Étape 4 - Traitement spécifique selon le type de champ
        switch ($type) {
            case 'email':
                // Supprime tous les caractères non autorisés dans un email
                $valeur = filter_var($valeur, FILTER_SANITIZE_EMAIL);
                // Si l'email n'est pas valide on retourne une chaîne vide
                if (!filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
                    return '';
                }
                break;

            case 'texte':
                // Supprime les caractères de contrôle invisibles (ex: null byte \0)
                $valeur = preg_replace('/[\x00-\x1F\x7F]/u', '', $valeur);
                // Supprime les tentatives d'injection SQL basiques
                $valeur = preg_replace('/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b|\bupdate\b)/i', '', $valeur);
                break;

            case 'message':
                // Supprime les caractères de contrôle invisibles
                $valeur = preg_replace('/[\x00-\x1F\x7F]/u', '', $valeur);
                // Supprime les tentatives d'injection SQL
                $valeur = preg_replace('/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b|\bupdate\b)/i', '', $valeur);
                // Limite la taille du message à 1000 caractères
                if (strlen($valeur) > 1000) {
                    $valeur = substr($valeur, 0, 1000);
                }
                break;

            default:
                return '';
        }

        return $valeur;
    }

    /**
     * @description Vérifie que l'email correspond au regex
     * @param string $email L'email à valider
     * @return string|null Message d'erreur ou null si valide
     */
    private function validerEmail(string $email): ?string
    {
        // Étape 1 - Vérifie que l'email n'est pas vide
        if (empty($email)) {
            return 'Email obligatoire';
        }
        // Étape 2 - Vérifie que l'email correspond à un format valide avec une regex stricte
        // caractères autorisés, @,.com ou .fr
        if (!preg_match(
              '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|fr)$/', 
              $email
               )){
            return 'Format email invalide';
        }
        return null;
    }

    /**
     * @description Vérifie que le sujet correspond au regex
     * @param string $sujet Le sujet à valider
     * @return string|null Message d'erreur ou null si valide
     */
    private function validerSujet(string $sujet): ?string
    {
        // Étape 1 - Vérifie si le parametre d'entrée est vide ou non 
        if (empty($sujet)) {
            return 'Sujet du mail obligatoire';
        }

        // Étape 2 - Vérifie que le sujet ne contient que des lettres, chiffres, espaces et tirets, 
        // et qu'il fait entre 3 et 100 caractères
        if (!preg_match('/^[a-zA-ZÀ-ÿ0-9\s\-]{3,100}$/', $sujet)) {
            return 'Sujet invalide (3 à 100 caractères)';
        }
        return null;
    }

    /**
     * @description Vérifie que le contenu du message est correct
     * @param string $message Le message à valider
     * @return string|null Message d'erreur ou null si valide
     */
    private function validerMessage(string $message): ?string
    {
        // Étape 1 - Vérifie si le parametre d'entrée est vide ou non 
        if (empty($message)) {
            return 'Message obligatoire';
        }

        // Étape 2 - Vérifie que la taille du message est inf à 500 
        if (strlen($message) > 500) {
            return 'Message trop long (500 caractères max)';
        }
        return null;
    }
    
    /**
     * @description Vérifie toutes les protections de sécurité avant de traiter la requête
     * @param Request $request La requête HTTP
     * @param RateLimiterFactory $contactLimiter Le limiteur de requêtes
     * @param array|null $data Les données JSON décodées
     * @return JsonResponse|null Retourne une erreur si une protection est déclenchée, null si tout est OK
     */
    private function verifierSecurite(Request $request, RateLimiterFactory $contactLimiter, ?array $data): ?JsonResponse
    {
        // Étape 1 - Mise en place de la protection 1, Rate Limiting Limite à 5 requêtes par heure par adresse IP
        $limiter = $contactLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Trop de requêtes, réessayez dans 1 heure'
            ], 429);
        }

        // Étape 2 - Mise en place de la protection 2, Vérifie que le Content-Type est bien du JSON
        if ($request->getContentTypeFormat() !== 'json') {
            return $this->json([
                'status'  => 'error',
                'message' => 'Content-Type invalide, JSON attendu'
            ], 415);
        }

        // Étape 3 - Mise en place de la protection 3, Limite la taille du body à 10Ko
        if (strlen($request->getContent()) > 10240) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Requête trop volumineuse'
            ], 413);
        }

        // Étape 4 - Mise en place de la protection 4, Honeypot Si le champ site_web est rempli c'est un bot
        if (!empty($data['site_web'])) {
            return $this->json(['status' => 'success', 'message' => 'Message envoyé avec succès'], 200);
        }

        // Étape 5 Retourne null si est OK
        return null;
    }

}