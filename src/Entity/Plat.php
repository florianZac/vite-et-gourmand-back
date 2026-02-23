<?php

namespace App\Entity;

use App\Repository\PlatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlatRepository::class)]
#[ORM\Table(name: 'plat')]
class Plat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'plat_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $titre_plat = null;

    #[ORM\Column(length: 255)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Allergene>
     */
    #[ORM\ManyToMany(targetEntity: Allergene::class)]
    #[ORM\JoinTable(
        name: 'contient',
        joinColumns: [new ORM\JoinColumn(name: 'plat_id', referencedColumnName: 'plat_id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'allergene_id', referencedColumnName: 'allergene_id')]
    )]
    private Collection $allergenes;

    public function __construct()
    {
        $this->allergenes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitrePlat(): ?string
    {
        return $this->titre_plat;
    }

    public function setTitrePlat(string $titre_plat): static
    {
        $this->titre_plat = $titre_plat;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return Collection<int, Allergene>
     */
    public function getAllergenes(): Collection
    {
        return $this->allergenes;
    }

    public function addAllergene(Allergene $allergene): static
    {
        if (!$this->allergenes->contains($allergene)) {
            $this->allergenes->add($allergene);
        }

        return $this;
    }

    public function removeAllergene(Allergene $allergene): static
    {
        $this->allergenes->removeElement($allergene);

        return $this;
    }
}
