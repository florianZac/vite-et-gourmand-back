<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\MailerService;
use App\Entity\Utilisateur;

#[Route('')]
final class TestEmailController extends AbstractController
{
  /**
   * @description permet l'envoie rapid de mail pour tester sur swagger l'inplantation de SendPit
   * @param mailerService $MailerService Le service mail
   * @return JsonResponse
   * 
   */
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