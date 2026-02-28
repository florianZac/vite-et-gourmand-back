<?php

namespace App\EventSubscriber;

use App\Service\LogService;
use App\Entity\Utilisateur;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @author      Florian Aizac
 * @created     28/02/2026
 * @description Subscriber qui écoute les événements de connexion Symfony
 * 
 * Pourquoi un EventSubscriber et pas directement dans AuthController ?
 * 
 * La route /api/login utilise json_login de Symfony Security.
 * 
 * Symfony intercepte la requête AVANT d'appeler le controller.
 * Son but est de géréer l'authentification de façon automatique de ce fait la méthode login() dans AuthController n'est JAMAIS appelée.
 * 
 * Du coup il faut écouter l'événement "LoginSuccessEvent" qui est déclenché automatiquement par Symfony après une authentification réussie.
 * 
 * Comparaison SQL vs NoSQL pour ce cas d'usage particulier :
 * 
 *  MySQL est une BDD relationnel : chaque log nécessiterait une table avec un schéma fixe, avec migration et jointure pour et entre les rapports sa n'a pas d'interet dans ce cas.
 * 
 *  MongoDB n'est pas une relationnel : Elle permet l'insertion rapide, sans schémas définit au préalable et qui n'a pas besoin de migration chaque log est dissocié et peut avoir des champs différents 
 *  
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    // Injection du LogService via l'autowiring Symfony
    public function __construct(private LogService $logService) {}

    /**
     * @description Déclare les événements écoutés par ce subscriber
     * LoginSuccessEvent est déclenché automatiquement par Symfony après chaque connexion réussie
     * @return array tableau associatif [événement => méthode]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    /**
     * @description Méthode appelée automatiquement après une connexion réussie
     * @param LoginSuccessEvent $event L'événement contenant les infos de l'utilisateur connecté
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        // Étape 1 - Récupère l'utilisateur qui vient de se connecter
        $utilisateur = $event->getUser();

        // Étape 2 - Vérifie que c'est bien une instance de notre entité Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return;
        }

        // Étape 3 - Récupère le rôle de l'utilisateur pour le contexte du log
        // getRoles() retourne un tableau, on prend le premier rôle
        $roles = $utilisateur->getRoles();
        $role = !empty($roles) ? $roles[0] : 'ROLE_UNKNOWN';

        // Étape 4 - Enregistre le log de connexion dans MongoDB
        $this->logService->log(
            'connexion',                                    // type de l'action
            $utilisateur->getUserIdentifier(),             // email de l'utilisateur
            $role,                                          // son rôle principal
            [                                           // contexte libre : infos utiles 
                'nom'    => $utilisateur->getNom(),
                'prenom' => $utilisateur->getPrenom(),
                'ville'  => $utilisateur->getVille(),
            ]
        );
    }
}
