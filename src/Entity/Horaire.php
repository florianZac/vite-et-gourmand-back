<?php
namespace App\Entity;

use App\Repository\HoraireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HoraireRepository::class)]
#[ORM\Table(name: 'horaire')]
class Horaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'horaire_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $jour = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heure_ouverture = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heure_fermeture = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJour(): ?string
    {
        return $this->jour;
    }

    public function setJour(string $jour): static
    {
        $this->jour = $jour;
        return $this;
    }

    public function getHeureOuverture(): ?\DateTime
    {
        return $this->heure_ouverture;
    }

    public function setHeureOuverture(\DateTime $heure_ouverture): static
    {
        $this->heure_ouverture = $heure_ouverture;
        return $this;
    }

    public function getHeureFermeture(): ?\DateTime
    {
        return $this->heure_fermeture;
    }

    public function setHeureFermeture(\DateTime $heure_fermeture): static
    {
        $this->heure_fermeture = $heure_fermeture;
        return $this;
    }
}