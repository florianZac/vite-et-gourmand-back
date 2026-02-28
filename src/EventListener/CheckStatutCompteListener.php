<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

// FONCTION -> Bloquer la connexion des comptes inactifs 
/** 

 * @description Vérifie que le compte est actif avant de laisser l'utilisateur se connecter
 * EventListener s'exécute automatiquement à chaque tentative de connexion. 
 * Si le compte est inactif, on lève une exception et la connexion est refusée.
 * @see https://symfony.com/doc/current/event_dispatcher.html
 * 
 */
#[AsEventListener(event: CheckPassportEvent::class)]
class CheckStatutCompteListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        // Étape 1 - Récupèrer l'utilisateur en cours de connection
        $user = $event->getPassport()->getUser();

        // Étape 2 - Vérifier que c'est bien un Utilisateur valide dans la BDD
        if (!$user instanceof Utilisateur) {
            return;
        }

        // Étape 3 - Bloquer si le compte est inactif
        if ($user->getStatutCompte() === 'inactif') {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte a été désactivé. Contactez l\'administrateur.'
            );
        }
        // Étape 4 - Retourner une érreur ?  
    }
}