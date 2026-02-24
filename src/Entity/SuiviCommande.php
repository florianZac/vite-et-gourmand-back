<?php

namespace App\Entity;

use App\Repository\SuiviCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: SuiviCommandeRepository::class)]
class SuiviCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'suivi_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $date_statut = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'commande_id', nullable: false)]
    private ?Commande $commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateStatut(): ?\DateTime
    {
        return $this->date_statut;
    }

    public function setDateStatut(\DateTime $date_statut): static
    {
        $this->date_statut = $date_statut;

        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }
}
