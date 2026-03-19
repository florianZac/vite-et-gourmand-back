<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\MailerService;
use App\Entity\Utilisateur;
use OpenApi\Annotations as OA;

#[Route('')]
final class TestEmailController extends AbstractController
{
  /**
   * @description permet l'envoie rapid de mail pour tester sur swagger l'inplantation de SendPit
   * @param mailerService $MailerService Le service mail
   * @return JsonResponse
   */
  #[Route('/test-email', name: 'api_test_email', methods: ['POST'])]
  #[OA\Post(
      summary: 'Envoyer un email de test',
      description: 'Envoie un email de bienvenue à un utilisateur factice pour tester le mailer.',
  )]
  #[OA\Tag(name: 'Admin - Test Email')]
  #[OA\Response(response: 200, description: 'Email de test envoyé !')]
  #[OA\Response(response: 400, description: 'Email manquant ou invalide')]
  public function sendTestEmail(MailerService $mailerService): JsonResponse
  {
    // Récupère l'email depuis le POST JSON si fourni
    $data = json_decode(file_get_contents('php://input'), true);
    $emailDest = $data['email'] ?? 'admin@vite-et-gourmand.fr'; // email par défaut

    // Crée un utilisateur factice avec l'email
    $utilisateur = new Utilisateur();
    $utilisateur->setPrenom('Test');
    $utilisateur->setEmail($emailDest);

    // Envoie l'email de bienvenue
    $mailerService->sendWelcomeEmail($utilisateur);

    return $this->json(['message' => 'Email de test envoyé à ' . $emailDest]);
  }
}