<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author      Florian Aizac
 * @created     28/02/2026
 * @description Document MongoDB représentant un log d'activité
 * 
 * Pourquoi MongoDB et pas MySQL pour les logs ?
 *      Les logs sont des données volumineuses sans structure fixe
 *      Chaque log peut avoir des données différentes (contexte variable)
 *      Pas besoin de relations entre les logs
 *      MongoDB est optimisé pour l'écriture rapide et la lecture par filtres
 *      MySQL est optimisé pour les données relationnelles structurées (commandes, utilisateurs...)
 * 
 * Types de logs enregistrés :
 *      connexion       : un utilisateur se connecte
 *      commande_creee  : une commande est créée
 *      commande_annulee: une commande est annulée
 *      statut_change   : le statut d'une commande change
 *      inscription     : un nouvel utilisateur s'inscrit
 */
#[ODM\Document(collection: 'logs_activite')]
class LogActivite
{
    // ==========================
    // Identifiant MongoDB
    // ==========================
    #[ODM\Id]
    private ?string $id = null;

    // ==========================
    // Type d'action loggée
    // ex: connexion, commande_creee, commande_annulee, statut_change, inscription
    // ==========================
    #[ODM\Field(type: 'string')]
    private string $type;

    // ==========================
    // Message descriptif du log
    // ex: "Client florian@email.fr s'est connecté"
    // ==========================
    #[ODM\Field(type: 'string')]
    private string $message;

    // ==========================
    // Email de l'utilisateur concerné
    // ==========================
    #[ODM\Field(type: 'string')]
    private string $email;

    // ==========================
    // Rôle de l'utilisateur concerné
    // ex: ROLE_CLIENT, ROLE_EMPLOYE, ROLE_ADMIN
    // ==========================
    #[ODM\Field(type: 'string')]
    private string $role;

    // ==========================
    // Données contextuelles supplémentaires
    // ex: { "numero_commande": "CMD-XXXX", "montant": 450.00 }
    // chaque log peut avoir des données différentes
    // sans modifier le schéma de la base
    // ==========================
    #[ODM\Field(type: 'hash')]
    private array $contexte = [];

    // ==========================
    // Date et heure du log
    // ==========================
    #[ODM\Field(type: 'date')]
    private \DateTime $createdAt;

    // ==========================
    // Constructeur : initialise la date automatiquement
    // ==========================
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ==========================
    // Getters / Setters
    // ==========================

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getContexte(): array
    {
        return $this->contexte;
    }

    public function setContexte(array $contexte): self
    {
        $this->contexte = $contexte;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}