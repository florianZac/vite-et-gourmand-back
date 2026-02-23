<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Symfony gère le login automatiquement via json_login
        // Cette méthode ne sera jamais appelée directement
        throw new \Exception('Ne devrait pas être appelé directement');
    }
}