<?php

namespace App\EventSubscriber;

use App\Entity\Commande;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CommandeStatutSubscriber implements EventSubscriber
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    /**
     * On indique à Doctrine quels événements on écoute
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    /**
     * Méthode appelée AVANT la mise à jour en base
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // On ne traite QUE les Commande
        if (!$entity instanceof Commande) {
            return;
        }

        // Vérifie que le champ "statut" a changé
        // remplit automatiquement dès que le statut devient "Livré" ou En attente du retour de matériel .
        if ($args->hasChangedField('statut')) {
            $newStatut = $args->getNewValue('statut');

            // Quand la commande est passée à "Livré"
            if ($newStatut === 'Livré') {
                $entity->setDateStatutLivree(new \DateTime());
            }

            // Optionnel : gérer l'attente de retour matériel
            if ($newStatut === 'En attente du retour de matériel') {
                $entity->setDateStatutRetourMateriel(new \DateTime());
            }
        }

    }
}