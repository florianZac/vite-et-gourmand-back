<?php
namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'role')] // force le nom de la table à 'role'
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'role_id')] // force le nom de la colonne à 'role_id'
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