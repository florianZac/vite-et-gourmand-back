<?php

namespace App\EventSubscriber;

use App\Entity\Commande;
use App\Enum\CommandeStatut;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

// Enregistrer automatiquement les dates de changement de statut d'une commande
/**
 * @author      Florian Aizac
 * @created     28/02/2026
 * @description Écoute les mises à jour Doctrine sur l'entité Commande.
 * Quand le champ "statut" change, on enregistre automatiquement la date du changement.
 * Le subscriber s'exécute AVANT l'écriture en base (preUpdate).
 *
 * Responsabilité de ce subscriber :
 *  - Enregistrer date_statut_retour_materiel quand le statut passe à EN_ATTENTE_RETOUR_MATERIEL
 *
 * Note : date_statut_livree est gérée directement dans EmployeController::changerStatut()
 * pour plus de lisibilité et de contrôle explicite.
 *
 * @see https://symfony.com/doc/current/doctrine/events.html
 * 
 *  1. preUpdate : Méthode appelée automatiquement AVANT la mise à jour en base
 * 
 */
// L'attribut #[AsDoctrineListener] remplace l'ancienne interface + getSubscribedEvents()
// On déclare ici directement quel événement Doctrine on écoute
#[AsDoctrineListener(event: Events::preUpdate)]
class CommandeStatutSubscriber
{
	// SUPPRESSION de getSubscribedEvents() : plus nécessaire avec #[AsDoctrineListener]
	// L'événement est déclaré directement dans l'attribut au dessus de la classe

	/**
	 * @description Méthode appelée automatiquement AVANT la mise à jour en base
	 * @param PreUpdateEventArgs $args Les arguments contenant l'entité et les champs modifiés
	 */
	public function preUpdate(PreUpdateEventArgs $args): void
	{
		// Étape 1 - Récupérer l'entité en cours de mise à jour
		$entity = $args->getObject();

		// Étape 2 - On ne traite QUE les entités Commande
		if (!$entity instanceof Commande) {
			return;
		}

		// Étape 3 - Vérifier que le champ "statut" a changé
		if ($args->hasChangedField('statut')) {

			// Étape 4 - Récupérer la nouvelle valeur du statut
			$newStatut = $args->getNewValue('statut');

			// Étape 5 - Quand la commande passe au statut "En attente du retour matériel"
			// on enregistre la date de début d'attente
			// Note : date_statut_livree est gérée dans EmployeController::changerStatut()
			if ($newStatut === CommandeStatut::EN_ATTENTE_RETOUR_MATERIEL) {
				$entity->setDateStatutRetourMateriel(new \DateTime());
			}
		}
	}
}
