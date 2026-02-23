<?php

namespace App\Entity;

use App\Repository\AllergeneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AllergeneRepository::class)]
#[ORM\Table(name: 'allergene')]
class Allergene
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'allergene_id')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }
}
