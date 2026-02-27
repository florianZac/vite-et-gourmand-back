<?php

namespace App\Entity;

use App\Repository\PasswordResetTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author      Florian Aizac
 * @created     26/02/2026
 * @description Entité de stockage des tokens de réinitialisation de mot de passe
 * 
 * Algo :
 * 1. Génèration d'un token aléatoire et unique.
 * 2. Association à un utilisateur.
 * 3. Expiration 4 heures par défaut
 * 4. Vérifier que le token est encore validé avant reset
 * 5. Suppression une fois utilisé
 */
#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_token')]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'token_id', type: 'integer')]
    private ?int $id = null;

    /**
     * Token unique et aléatoire envoyé par email à l'utilisateur
     */
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $token = null;

    /**
     * Utilisateur associé à ce token (relation ManyToOne)
     * Un utilisateur peut avoir plusieurs tokens due à des réinitializations successives
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'utilisateur_id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    /**
     * Date et heure de création du token
     * Permet de calculer l'expiration du token
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $createdAt = null;

    /**
     * Date et heure d'expiration du token
     * Au-delà de cette date, le token ne peut plus être utilisé
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $expiresAt = null;

    /**
     * Indique si le token a déjà été utilisé
     * Une fois que l'utilisateur a réinitialisé son mot de passe avec ce token,
     * nous le marquons comme utilisé et il ne peut plus être réutilisé
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isUsed = false;

    // ==========================
    // Getters / Setters
    // ==========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(bool $isUsed): static
    {
        $this->isUsed = $isUsed;
        return $this;
    }

    /**
     * Vérifie si le token est encore valide (non expiré et non utilisé)
     * Cette méthode est appelée avant de permettre la réinitialisation
     * @return bool true si le token peut être utilisé, false sinon
     */
    public function isValid(): bool
    {
        // Token n'est valide que si:
        // 1. Il n'a pas déjà été utilisé
        // 2. La date d'expiration n'est pas passée
        return !$this->isUsed && new \DateTime() <= $this->expiresAt;
    }
}
