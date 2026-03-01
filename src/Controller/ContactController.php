<?php

namespace App\Controller;

use App\Service\MailerService;
use App\Service\SanitizerService;
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
 *  1. contact               : Récupère les données de contact d'un formulaire
 *  2. validationDataContact : Teste la validation de tous les champs du formulaire de contact
 *  3. validerEmail          : Vérifie que l'email correspond au regex
 *  4. validerSujet          : Vérifie que le sujet correspond au regex
 *  5. validerMessage        : Vérifie que le contenu du message est correct
 *  6. verifierSecurite      : Vérifie toutes les protections de sécurité avant de traiter la requête
 */
#[Route('/api')]
class ContactController extends AbstractController
{
    public function __construct(private SanitizerService $sanitizer) {}

    /**
     * @description Récupère les données de contact d'un formulaire
     * Reçoit un JSON avec le sujet, email et le message, valide et envoie un email
     * à l'administrateur du site via le service MailerService
     */
    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    /**
     * @description : Récupere les données de contact d'un formulaire
     * Reçoit un JSON avec le sujet, email et le message, valide et envoie un email à l'administrateur du site via le service MailerService
    */
    public function contact(
        Request $request,
        MailerService $mailerService,
        #[Target('contact_limiter.limiter')] RateLimiterFactory $contactLimiter
    ): JsonResponse {
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
        // Étape 1 - Création du tableau de stockage des erreurs de validation
        $erreurs = [];

        // Étape 2 - Nettoyage des données avant validation via le SanitizerService
        $email   = isset($data['email'])   ? $this->sanitizer->sanitize($data['email'],   'email')   : '';
        $sujet   = isset($data['sujet'])   ? $this->sanitizer->sanitize($data['sujet'],   'texte')   : '';
        $message = isset($data['message']) ? $this->sanitizer->sanitize($data['message'], 'message') : '';

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

        // Étape 2 - Vérifie que l'email correspond à un format valide
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|fr)$/', $email)) {
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
        // Étape 1 - Vérifie que le sujet n'est pas vide
        if (empty($sujet)) {
            return 'Sujet du mail obligatoire';
        }

        // Étape 2 - Vérifie que le sujet ne contient que des lettres, chiffres, espaces et tirets
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
        // Étape 1 - Vérifie que le message n'est pas vide
        if (empty($message)) {
            return 'Message obligatoire';
        }

        // Étape 2 - Vérifie que la taille du message est inférieure à 500 caractères
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
     * @return JsonResponse|null Retourne une erreur si une protection est déclenchée, null sinon
     */
    private function verifierSecurite(Request $request, RateLimiterFactory $contactLimiter, ?array $data): ?JsonResponse
    {
        // Étape 1 - Rate Limiting : limite à 25 requêtes par heure par adresse IP
        $limiter = $contactLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Trop de requêtes, réessayez dans 1 heure'
            ], 429);
        }

        // Étape 2 - Vérifie que le Content-Type est bien du JSON
        if ($request->getContentTypeFormat() !== 'json') {
            return $this->json([
                'status'  => 'error',
                'message' => 'Content-Type invalide, JSON attendu'
            ], 415);
        }

        // Étape 3 - Limite la taille du body à 10Ko
        if (strlen($request->getContent()) > 10240) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Requête trop volumineuse'
            ], 413);
        }

        // Étape 4 - Honeypot : si le champ site_web est rempli c'est un bot
        if (!empty($data['site_web'])) {
            return $this->json(['status' => 'success', 'message' => 'Message envoyé avec succès'], 200);
        }

        // Étape 5 - Tout est OK
        return null;
    }
}
