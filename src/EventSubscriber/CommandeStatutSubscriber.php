<?php

namespace App\EventSubscriber;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

// FONCTION -> Enregistrer automatiquement les dates de changement de statut d'une commande
/**
 * @description Écoute les mises à jour Doctrine sur l'entité Commande.
 * Quand le champ "statut" change, on enregistre automatiquement la date du changement.
 * Le subscriber s'exécute AVANT l'écriture en base (preUpdate).
 * @see https://symfony.com/doc/current/doctrine/events.html
 */

// l'attribut #[AsDoctrineListener] remplace l'ancienne interface + getSubscribedEvents()
// On déclare ici directement quel événement Doctrine on écoute
#[AsDoctrineListener(event: Events::preUpdate)]
class CommandeStatutSubscriber
{
    // SUPPRESSION de getSubscribedEvents() : plus nécessaire avec #[AsDoctrineListener]
    // L'événement est déclaré directement dans l'attribut au dessus de la classe

    /**
     * Méthode appelée AVANT la mise à jour en base
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // Récupère l'entité en cours de mise à jour
        $entity = $args->getObject();

        // On ne traite QUE les Commande
        if (!$entity instanceof Commande) {
            return;
        }

        // Vérifie que le champ "statut" a changé
        // remplit automatiquement dès que le statut devient "Livré" ou "En attente du retour matériel"
        if ($args->hasChangedField('statut')) {

            // On Récupère la nouvelle valeur du statut
            $newStatut = $args->getNewValue('statut');

            // Quand la commande passe au statut "Livré" on enregistre la date pour le calcul des 10 jours ouvrés
            if ($newStatut === 'Livré') {
                $entity->setDateStatutLivree(new \DateTime());
            }

            // Quand la commande passe au statut "En attente du retour matériel" on enregistre la date de début d'attente
            if ($newStatut === 'En attente du retour matériel') {
                $entity->setDateStatutRetourMateriel(new \DateTime());
            }
        }
    }
}
