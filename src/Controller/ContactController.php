<?php
namespace App\Controller;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
/**
 * @author      Florian Aizac
 * @created     23/02/2026
 * @description Contrôleur gérant l'envoi de messages de contact
 */

#[Route('/api')]
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    // Reçoit un JSON avec nom, email, message et envoie un email
    public function contact(Request $request, MailerService $mailerService): JsonResponse
    {
        // Récupère les données JSON envoyées par le client
        $data = json_decode($request->getContent(), true);

        // Vérifie que les champs obligatoires sont présents
        if (empty($data['sujet']) || empty($data['email']) || empty($data['message'])) {
            return $this->json(['message' => 'Champs manquants : sujet, email, message obligatoires'], 400);
        }

        // Envoie l'email via le service
        $mailerService->sendContactEmail($data);

        return $this->json(['message' => 'Message envoyé avec succès'], 200);
    }
}