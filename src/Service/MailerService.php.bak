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
    // Le constructeur du Mailer de Symfony
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
     * @description Envoie un email avec le lien de réinitialisation de mot de passe
     *
     * Cette fonction envoie à l'utilisateur un email contenant :
     * 1. Un lien sécurisé avec token unique
     * 2. Les conditions de la réinitialisation (délai expiration 4 heure, Reggex mot de passe)
     * 3. Un rappel de sécurité au cas où ce ne serait pas lui qui a demandé la réinitialisation du mdp
     * 
     * @param Utilisateur $utilisateur L'utilisateur qui demande la réinitialisation
     * @param string $resetLink Le lien complet avec token à envoyer par email
     * @return void retourne rien
     */
    //public function sendPasswordResetEmail(Utilisateur $utilisateur, string $motDePasseTemporaire): void
    public function sendPasswordResetEmail(Utilisateur $utilisateur, string $resetLink): void
    {
        // Étape 1 - Générer le contenu HTML à partir du template avec le lien de reset
        // $html = $this->twig->render('emails/password_reset.html.twig', [
        $html = $this->twig->render('emails/password_reset_link.html.twig', [
            'prenom'      => $utilisateur->getPrenom(),
            //'motDePasse' => $motDePasseTemporaire,
            'reset_link'  => $resetLink,
        ]);

        // Étape 2 - Créer et configurer l'email
        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html);

        // Étape 3 - Envoyer l'email
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

    /**
     * @description Envoie un email à l'admin pour lui signaler une demande de désactivation de compte
     * @param Utilisateur $utilisateur Le client qui demande la désactivation
     * @return void
     */
    public function sendDemandeDesactivationEmail(Utilisateur $utilisateur): void
    {
        $html = $this->twig->render('emails/demande_desactivation.html.twig', [
            'prenom' => $utilisateur->getPrenom(),
            'nom'    => $utilisateur->getNom(),
            'email'  => $utilisateur->getEmail(),
        ]);

        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to('admin@vite-et-gourmand.fr')
            ->subject('Demande de désactivation de compte client ' . $utilisateur->getEmail())
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * @description Envoie un email au client lorsque le matériel doit être retourné
     * @param Utilisateur $utilisateur
     * @param Commande $commande
     */
    public function sendRetourMaterielEmail(Utilisateur $utilisateur, Commande $commande): void
    {
        $html = $this->twig->render('emails/retour_materiel.html.twig', [
            'prenom'          => $utilisateur->getPrenom(),
            'numero_commande' => $commande->getNumeroCommande(),
            'date_prestation' => $commande->getDatePrestation()->format('d/m/Y'),
        ]);

        $email = (new Email())
            ->from('noreply@vite-et-gourmand.fr')
            ->to($utilisateur->getEmail())
            ->subject('Retour du matériel – Commande ' . $commande->getNumeroCommande())
            ->html($html);

        $this->mailer->send($email);
    }

   /**
     * Envoie un email au client pour la restitution du matériel.
     *
     * - Utilise un template Twig pour le HTML
     * - Permet d’injecter dynamiquement prénom, numéro de commande, date de prestation
     * - Sujet dynamique incluant le numéro de commande
     *
     * @param Utilisateur $utilisateur Le client destinataire
     * @param Commande $commande La commande concernée
     * @param int $montant Pénalité fixe (600€)
     * @param \DateTime|null $dateRestitution Date limite de restitution à afficher dans le mail
     * @utiisation
     * $dateRestitution = $this->dateService->addBusinessDays($commande->getDateStatutLivree(), 10);

     * $this->mailerService->sendPenaliteMaterielEmail(
     *     $commande->getUtilisateur(),
     *     $commande,
     *     600,
     *     $dateRestitution
     * );
     */
    
    public function sendPenaliteMaterielEmail(
        Utilisateur $utilisateur,
        Commande $commande,
        int $montant = 600,
        ?\DateTime $dateRestitution = null
    ): void
    {
        // Étape 1 - Préparer le rendu HTML via Twig
        $html = $this->twig->render('emails/retour_materiel.html.twig', [
            'prenom'          => $utilisateur->getPrenom(),
            'numero_commande' => $commande->getNumeroCommande(),
            'date_prestation' => $commande->getDatePrestation()->format('d/m/Y'),
            'montant'         => $montant,
            'date_restitution'=> $dateRestitution ? $dateRestitution->format('d/m/Y') : null,
        ]);

        // Étape 2 - Construire l'objet email
        $email = (new Email()) // TemplatedEmail permet l'utilisation des templates Twig
            ->from('noreply@vite-et-gourmand.fr') // Expéditeur standard
            ->to($utilisateur->getEmail())       // Destinataire dynamique
            ->subject('Retour du matériel – Commande ' . $commande->getNumeroCommande()) // Sujet dynamique
            ->html($html); // Contenu HTML injecté depuis le template Twig

        // Étape 3 - Envoi du mail via le service MailerInterface
        $this->mailer->send($email);

        // Étape 4 - Optionnel : log ou retour pour suivi dans la Command
        //logger->info("Mail envoyé pour la commande X");
    }

    /**
     * @description Envoie un email de bienvenue à un nouvel utilisateur après son inscription
     * 
     * Cette fonction est appelée automatiquement lors de la création d'un compte utilisateur.
     * Elle permet d'accueillir l'utilisateur
     * 
     * @param Utilisateur $utilisateur L'utilisateur qui vient de s'inscrire
     * @return void retourne rien, envoie simplement un email
     */
    public function sendWelcomeEmail(Utilisateur $utilisateur): void
    {
        // Étape 1 - Générer le contenu HTML à partir du template Twig
        // On récupere le prénom de l'utilisateur 
        $html = $this->twig->render('emails/bienvenue.html.twig', [
            'prenom' => $utilisateur->getPrenom(),
        ]);

        // Étape 2 - Créer un objet Email avec les paramètres standard 
        $email = (new Email())
            // Expéditeur : on utilise l'email générique de l'entreprise pour tous les mails automatiques
            ->from('noreply@vite-et-gourmand.fr')
            // Destinataire : l'email de l'utilisateur qui vient de s'inscrire
            ->to($utilisateur->getEmail())
            // Sujet du mail : clair et direct pour que l'utilisateur le retrouve facilement
            ->subject('Bienvenue chez Vite & Gourmand')
            // Contenu : le HTML généré depuis le template Twig
            ->html($html);

        // Étape 3 - Envoyer l'email via le service MailerInterface
        // En développement, Mailtrap intercepte l'email et l'affiche dans la console
        // En production, l'email est envoyé réellement au destinataire
        $this->mailer->send($email);
    }

}