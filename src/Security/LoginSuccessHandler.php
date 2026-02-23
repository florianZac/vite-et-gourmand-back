<?php
namespace App\Security;

/*
 * Ce service est appelé par le firewall de Symfony après une authentification réussie.
 * Il génère un token JWT et renvoie les infos de l'utilisateur connecté.
 *
 * C'est grâce à ce service que notre frontend pourra récupérer le token JWT
 * et les infos de l'utilisateur pour les stocker dans le localStorage.
 *
 * C'est aussi grâce à ce service que notre frontend saura que l'authentification a réussi
 * et pourra rediriger l'utilisateur vers la page d'accueil.
 */
/* include */
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use App\Entity\Utilisateur;

/**
 * @author : florian Aizac
 * @create  : 23/02/2026
 * @description : ce service est appelé par le firewall de Symfony après une authentification réussie.
 * @see https://symfony.com/doc/current/security.html#authentication-identifying-logging-in-the-user
 *
 * utilisation  :
 *  $token = $this->jwtManager->create($utilisateur); // génère un token JWT pour l'utilisateur connecté
 *      return new JsonResponse([ // renvoie le token JWT et les infos de l'utilisateur
 *          'token' => $jwt,
 *          'utilisateur' => [
 *              'email'  => $utilisateur->getEmail(),
 *              'prenom' => $utilisateur->getPrenom(),
 *              'role'   => $utilisateur->getRoles()[0],
 *          ]
 *      ]);  
 *  */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    // creation de la variable de gestion du service des tokens JWT
    private JWTTokenManagerInterface $jwtManager;

    // constructeur de l'objet qui reçoit le service de gestion des tokens JWT
    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        // on récupere le service injecté par Symfony dans le constructeur de l'objet.
        // Ensuite on stocke le service de gestion des tokens JWT dans la variable de classe 
        //pour pouvoir l'utiliser dans la méthode onAuthenticationSuccess() 
        $this->jwtManager = $jwtManager;
    }

    /**
     * @role : florian Aizac
     * @create : 23/02/2026
     * @description : cette méthode est appelée par le firewall de Symfony après une authentification réussie.
     * @param Request $request requête HTTP reçue
     * @param TokenInterface $token contient l’utilisateur authentifié
     * @return JsonResponse retourne une réponse JSON contenant le token JWT et les infos de l'utilisateur connecté(email et prénom et role)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        // On récupère l'utilisateur connecté 
        $utilisateur = $token->getUser();

        // On génère le token JWT pour cet utilisateur
        $jwt = $this->jwtManager->create($utilisateur);

        // On renvoie le token ainsi que les infos de l'utilisateur
        return new JsonResponse([
            'token' => $jwt,
            'utilisateur' => [
                'email'  => $utilisateur->getEmail(),
                'prenom' => $utilisateur->getPrenom(),
                'role'   => $utilisateur->getRoles()[0],
            ]
        ]);
    }
}