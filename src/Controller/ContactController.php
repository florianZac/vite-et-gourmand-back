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
 */

#[Route('/api')]
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    /**
     * @description : Point d'entrée de la route POST /api/contact
     * Reçoit un JSON avec le sujet, email et le message, valide et envoie un email à l'administrateur du site via le service MailerService
    */
    public function contact(
            Request $request, 
            MailerService $mailerService, 
            #[Target('contact_limiter.limiter')] RateLimiterFactory $contactLimiter
        ): JsonResponse
    {
        // Récupère et décode les données JSON envoyées par le client
        $data = json_decode($request->getContent(), true);

        // Vérifie toutes les protections de sécurité
        // Si la fonction retourne une JsonResponse c'est qu'une protection a été déclenchée
        $securite = $this->verifierSecurite($request, $contactLimiter, $data);
        if ($securite !== null) {
            return $securite;
        }

        // Vérifie que les données sont valides
        if (!$data) {
            // Si le JSON est invalide, retourne une erreur avec un code 400
            return $this->json(['status' => 'error', 'message' => 'Format JSON invalide'], 400);
        }

        // Appel de la fonction de validation qui retourne un tableau d'erreurs
        $erreurs = $this->validationDataContact($data);

        // Si des erreurs existent on les retourne toutes au front avec un code 400
        if (!empty($erreurs)) {
            return $this->json(['status' => 'error', 'erreurs' => $erreurs], 400);
        }

        // Tout est valide, on envoie l'email via le service
        $mailerService->sendContactEmail($data);

        // Retourne une réponse de succès avec un code 200
        return $this->json(['status' => 'success', 'message' => 'Message envoyé avec succès'], 200);
    }

    /**
     * @description Orchestre la validation de tous les champs du formulaire de contact
     * @param array $data Les données à valider
     * @return array Tableau contenant les erreurs de validation, vide si tout est valide
     */
    private function validationDataContact(array $data): array
    {
        // Tableau qui va stocker toutes les erreurs de validation
        $erreurs = [];

        // Etape 1 — Nettoyage des données avant validation
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

        // Etape 2 — Validation de l'email
        // On appelle la fonction validerEmail avec l'email nettoyé
        // Si elle retourne une erreur on l'ajoute au tableau $erreurs
        $erreurEmail = $this->validerEmail($email);
        if ($erreurEmail) {
            $erreurs['email'] = $erreurEmail;
        }

        // Etape 3 — Validation du sujet
        $erreurSujet = $this->validerSujet($sujet);
        if ($erreurSujet) {
            $erreurs['sujet'] = $erreurSujet;
        }

        // Etape 4 — Validation du message
        $erreurMessage = $this->validerMessage($message);
        if ($erreurMessage) {
            $erreurs['message'] = $erreurMessage;
        }

        // Retourne le tableau d'erreurs, vide si tout est valide
        return $erreurs;
    }
        
    /**
     * @description Nettoie une valeur selon son type pour éviter les injections
     * @param string $valeur La valeur à nettoyer
     * @param string $type Le type de champ (email, texte, message)
     * @return string La valeur nettoyée
     */
    private function sanitizer(string $valeur, string $type): string
    {
        // Etape 1 — Supprime les espaces inutiles en début et fin de chaîne
        $valeur = trim($valeur);

        // Etape 2 — Supprime toutes les balises HTML et PHP
        $valeur = strip_tags($valeur);

        // Etape 3 — Convertit les caractères spéciaux HTML en entités
        $valeur = htmlspecialchars($valeur, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Etape 4 — Traitement spécifique selon le type de champ
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
     * @description Valide le format de l'email
     * @param string $email L'email à valider
     * @return string|null Message d'erreur ou null si valide
     */
    private function validerEmail(string $email): ?string
    {
        // Vérifie que l'email n'est pas vide
        if (empty($email)) {
            return 'Email obligatoire';
        }
        // Vérifie que l'email correspond à un format valide avec une regex stricte
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
     * @description Valide le sujet du message
     * @param string $sujet Le sujet à valider
     * @return string|null Message d'erreur ou null si valide
     */
    private function validerSujet(string $sujet): ?string
    {
        if (empty($sujet)) {
            return 'Sujet du mail obligatoire';
        }
        // Vérifie que le sujet ne contient que des lettres, chiffres, espaces et tirets, 
        //et qu'il fait entre 3 et 100 caractères
        if (!preg_match('/^[a-zA-ZÀ-ÿ0-9\s\-]{3,100}$/', $sujet)) {
            return 'Sujet invalide (3 à 100 caractères)';
        }
        return null;
    }

    /**
     * @description Valide le contenu du message
     * @param string $message Le message à valider
     * @return string|null Message d'erreur ou null si valide
     */
    private function validerMessage(string $message): ?string
    {
        if (empty($message)) {
            return 'Message obligatoire';
        }

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
        // Protection 1 — Rate Limiting
        // Limite à 5 requêtes par heure par adresse IP
        $limiter = $contactLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Trop de requêtes, réessayez dans 1 heure'
            ], 429);
        }

        // Protection 2 — Vérifie que le Content-Type est bien du JSON
        if ($request->getContentTypeFormat() !== 'json') {
            return $this->json([
                'status'  => 'error',
                'message' => 'Content-Type invalide, JSON attendu'
            ], 415);
        }

        // Protection 3 — Limite la taille du body à 10Ko
        if (strlen($request->getContent()) > 10240) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Requête trop volumineuse'
            ], 413);
        }

        // Protection 4 — Honeypot
        // Si le champ site_web est rempli c'est un bot
        if (!empty($data['site_web'])) {
            return $this->json(['status' => 'success', 'message' => 'Message envoyé avec succès'], 200);
        }

        // Tout est OK on retourne null
        return null;
    }

}