<?php

namespace App\Command;

use App\Repository\CommandeRepository;
use App\Service\MailerService;
use App\Service\DateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-retour-materiel', // Nom de la commande dans le terminal
    description: 'Vérifie les commandes dont le matériel n\'a pas été restitué et envoie un mail si nécessaire'
)]
class CheckRetourMaterielCommand extends Command
{
    /**
     * Injection des services nécessaires :
     * CommandeRepository : récupérer les commandes dans la base
     * MailerService : envoyer les mails
     * DateService : calculer la date limite de restitution
     * EntityManagerInterface : sauvegarder les modifications
     */
    public function __construct(
        private CommandeRepository $commandeRepository,
        private MailerService $mailerService,
        private DateService $dateService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    /**
     * Pas d'arguments ni d'options nécessaires pour ce cron job
     */
    protected function configure(): void
    {
        // Commande simple, pas de configuration spécifique
    }

    /**
     * @description Méthode exécutée lorsque la commande est lancée
     * @param InputInterface $input paramètre d'entrée de la donnée à exécuter
     * @param OutputInterface $input paramètre de sortie de la donnée à exécuter
     * @return int retoune success ou erreur.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('===== Début du check des commandes matériel non restitué =====');

        // Étape 1 - Récupérer toutes les commandes à relancer : pret_materiel=1, restitution_materiel=0, status=Livré
        $commandes = $this->commandeRepository->findCommandesMaterielARelancer();

        // Boucle sur chaque commande pour vérifier si on doit envoyer le mail
        foreach ($commandes as $commande) {

            // Étape 2 - Vérifier que la date de statut livrée existe
            if (!$commande->getDateStatutLivree()) {
                $output->writeln("Commande {$commande->getNumeroCommande()} n'a pas de date de statut livrée !");
                continue;
            }
            
            // Étape 3 - Date de restitution = date_statut_livree + 10 jours ouvrés
            $dateRestitution = $this->dateService->addOpenDay($commande->getDateStatutLivree(), 10);

            // Étape 4 - Vérifier si le mail a déjà été envoyé
            if ($commande->isMailPenaliteEnvoye()) {
                $output->writeln("Mail déjà envoyé pour commande " . $commande->getNumeroCommande());
                continue;
            }
        
            // Étape 4 - Comparer la date actuelle avec la date de restitution
            if (new \DateTime() >= $dateRestitution) {
                // Étape 5 - Envoyer le mail via le service MailerService
                $this->mailerService->sendPenaliteMaterielEmail(
                    $commande->getUtilisateur(),
                    $commande,
                    600, // montant de la pénalité
                    $dateRestitution // injection de la date limite dans le mail Twig
                );

                // Étape 6 - Marquer la commande comme ayant reçu le mail
                $commande->setMailPenaliteEnvoye(true);
                $this->entityManager->flush();

                // Log console pour suivi
                $output->writeln("Mail pénalité envoyé pour commande " . $commande->getNumeroCommande());

            } else {
                // Si la date de restitution n'est pas encore atteinte
                $output->writeln(
                    "Pas encore de mail pour commande " . $commande->getNumeroCommande() .
                    " (attente 10 jours ouvrés, date limite: " . $dateRestitution->format('d/m/Y') . ")"
                );
            }
        }

        $this->entityManager->flush();

        $output->writeln('===== Fin du check des commandes =====');

        // Retourner le code succès
        return Command::SUCCESS;
    }
}