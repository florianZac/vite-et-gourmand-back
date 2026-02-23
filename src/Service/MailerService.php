<?php

// Déclaration du service de messagerie pour que symfony puisse trouver cette classe et l'utiliser dans les controllers
namespace App\Service;

// Importe bibliotheque de l'interface Mailer de Symfony ou il y as l'ensemble des fct classe pour envoyer des emails
use Symfony\Component\Mailer\MailerInterface;
// Importe la classe Email, qui représente un email 
use Symfony\Component\Mime\Email;
// Importe l'environnement Twig pour pouvoir utiliser les templates d'emails.
use Twig\Environment;

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
}