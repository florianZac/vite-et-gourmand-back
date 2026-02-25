<?php

// Déclaration du service de messagerie pour que symfony puisse trouver cette classe et l'utiliser dans les controllers
namespace App\Service;

// Importe bibliotheque de l'interface Mailer de Symfony ou il y as l'ensemble des fct classe pour envoyer des emails
use Symfony\Component\Mailer\MailerInterface;
// Importe la classe Email, qui représente un email 
use Symfony\Component\Mime\Email;
// Importe l'environnement Twig pour pouvoir utiliser les templates d'emails.
use Twig\Environment;
// Importe la classe Utilisateur pour pouvoir l'utiliser dans la fonction de réinitialisation de mot de passe
use App\Entity\Utilisateur;
// Importe la classe Commande pour pouvoir l'utiliser dans la fonction d'annulation de commande
use App\Entity\Commande;

/**
 * @author      Florian Aizac
 * @created     23/02/2026
 * @description Service gérant l'envoi de tous les emails de l'application
 */
class MailerService
{
    // Le constructeur de onnée du Mailer de Symfony
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig) {}

    // Fonction email qui envoie un email de contact à l'administrateur du site lorsque le client remplis le formulaire de contact
    // reçoit un tableau $data contenant les données du formulaire (nom, email, message)
    public function sendContactEmail(array $data): void
    {
        // Génère le HTML à partir du template Twig en lui passant les variables
        $html = $this->twig->render('emails/contact.html.twig', [
            'sujet'   => $data['sujet'],
            'email'   => $data['email'],
            'message' => $data['message'],
        ]);

        // Crée et envoie l'email avec le HTML généré à partir du template Twig
        $email = (new Email())
            // Définit l'expéditeur de l'email
            ->from('noreply@vite-et-gourmand.fr')
            // Définit le destinataire (ici l'admin qui reçoit les messages de contact)
            ->to('admin@vite-et-gourmand.fr')
            // Définit le sujet de l'email
            ->subject('Nouveau message de contact')
            // Définit le contenu HTML de l'email
            ->html($html);

        // Envoie l'email via le Mailer de Symfony
        // C'est ici que Mailtrap intercepte l'email en développement
        $this->mailer->send($email);
    }

    /**
     * @description Envoie un email de réinitialisation de mot de passe à un utilisateur
     * Fonction appellée lors de la réinitialisation du mdp par l'admin
     * elle permet de générer un mot de passe temporaire aléatoire et d'envoyer un email au client.
     * @param Utilisateur $utilisateur l'utilisateur qui a demandé la réinitialisation de son mot de passe
     * @param string $motDePasseTemporaire : le mot de passe temporaire généré pour l'utilisateur
     * @return void retourne rien 
     */
    public function sendPasswordResetEmail(Utilisateur $utilisateur, string $motDePasseTemporaire): void
    {
        // Génère le HTML à partir du template Twig
        $html = $this->twig->render('emails/password_reset.html.twig', [
            'prenom'     => $utilisateur->getPrenom(),
            'motDePasse' => $motDePasseTemporaire,
        ]);

        // Crée l'email
        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html);

        // Envoie l'email
        $this->mailer->send($email);
    }

    /**
     * @description Envoie un email de confirmation d'annulation de commande au client
     * @param Utilisateur $utilisateur Le client qui annule
     * @param Commande $commande La commande annulée
     * @param int $pourcentage Le pourcentage de remboursement
     * @param float $montant Le montant remboursé au client
     * @return void retourne rien 
     */
    public function sendAnnulationEmail(Utilisateur $utilisateur, Commande $commande, int $pourcentage, float $montant): void
    {
        // Génère le HTML à partir du template Twig
        $html = $this->twig->render('emails/annulation_commande.html.twig', [
            'prenom'          => $utilisateur->getPrenom(),
            'numero_commande' => $commande->getNumeroCommande(),
            'date_prestation' => $commande->getDatePrestation()->format('d/m/Y'),
            'motif'           => $commande->getMotifAnnulation(),
            'pourcentage'     => $pourcentage,
            'montant'         => $montant,
        ]);

        // Création de l'email
        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Annulation de votre commande ' . $commande->getNumeroCommande())
            ->html($html);

        // Envoie de l'email
        $this->mailer->send($email);
    }

    /**
     * @description Envoie un email au client lorsque sa commande est acceptée
     * @param Utilisateur $utilisateur Le client
     * @param Commande $commande La commande acceptée
     * @return void
     */
    public function sendCommandeAccepteeEmail(Utilisateur $utilisateur, Commande $commande): void
    {
        // Permet de remplir les données pour l'envoies de l'Email à travers le template
        $html = $this->twig->render('emails/commande_acceptee.html.twig', [
            'prenom'          => $utilisateur->getPrenom(),
            'numero_commande' => $commande->getNumeroCommande(),
            'date_prestation' => $commande->getDatePrestation()->format('d/m/Y'),
        ]);

        // Création de l'email
        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Votre commande ' . $commande->getNumeroCommande() . ' a été acceptée')
            ->html($html);
            
        // Envoie de l'e-mail
        $this->mailer->send($email);
    }

    /**
     * @description Envoie un email au client lorsque sa commande est en livraison
     * @param Utilisateur $utilisateur Le client
     * @param Commande $commande La commande en livraison
     * @return void
     */
    public function sendCommandeLivraisonEmail(Utilisateur $utilisateur, Commande $commande): void
    {
        $html = $this->twig->render('emails/commande_livraison.html.twig', [
            'prenom'          => $utilisateur->getPrenom(),
            'numero_commande' => $commande->getNumeroCommande(),
            'date_prestation' => $commande->getDatePrestation()->format('d/m/Y'),
        ]);

        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Votre commande ' . $commande->getNumeroCommande() . ' est en cours de livraison')
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * @description Envoie un email au client lorsque sa commande est terminée avec invitation à déposer un avis
     * @param Utilisateur $utilisateur Le client
     * @param Commande $commande La commande terminée
     * @return void
     */
    public function sendCommandeTermineeEmail(Utilisateur $utilisateur, Commande $commande): void
    {
        $html = $this->twig->render('emails/commande_terminee.html.twig', [
            'prenom'          => $utilisateur->getPrenom(),
            'numero_commande' => $commande->getNumeroCommande(),
            'date_prestation' => $commande->getDatePrestation()->format('d/m/Y'),
        ]);

        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Votre commande ' . $commande->getNumeroCommande() . ' est terminée - Donnez votre avis !')
            ->html($html);

        $this->mailer->send($email);
    }
}