<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
/**
 * @author : florian Aizac
 * @create  : 23/02/2026
 * @description : ce service est appelé par le firewall de Symfony après une authentification échouée.
 * @see https://symfony.com/doc/current/security.html#authentication-identifying-logging-in-the-user
 * - après une authentification échouée, le firewall de Symfony appelle la méthode onAuthenticationFailure() de ce service.
 * @param Request $request requête HTTP reçue
 * @param AuthenticationException $exception contient les détails de l'erreur d'authentification
 * @return JsonResponse retourne une réponse JSON contenant un message d'erreur et un code d'erreur 401
*/
class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($_ENV['APP_ENV'] === 'dev') {
            // En développement on affiche le détail de l'erreur pour déboguer
            $reponse = [
                'error' => 'Email ou mot de passe incorrect',
                'debug' => $exception->getMessage()
            ];
        } else {
            // En production on cache le détail pour éviter les failles de sécurité
            $reponse = [
                'error' => 'Email ou mot de passe incorrect'
            ];
        }
        // ensuite on renvoie une réponse JSON avec le message d'erreur et un code HTTP 401 Unauthorized
        return new JsonResponse($reponse, Response::HTTP_UNAUTHORIZED);
    }
}
