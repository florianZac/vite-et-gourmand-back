<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    // ==========================
    // Identifiant et propriétés de base
    // ==========================
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'commande_id', type: 'integer')]
    private ?int $id = null;

    /**
    * Numéro unique de la commande
    */
    #[ORM\Column(length: 50)]
    private ?string $numero_commande = null;

    /**
    * Date de création de la commande
    */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)] // DATETIME_MUTABLE pour stocker la date ET l'heure
    private ?\DateTime $date_commande = null;

    /**
    * Date de prestation de la commande
    */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_prestation = null; // correction de la faute de frappe date_prestattion -> date_prestation

    /**
    * Statut de la commande (ex: Livré, Terminée, etc.)
    */   
    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    // ==========================
    // Matériel
    // ==========================

    /**
    * Indique si le matériel a été prêté (true = prêté)
    */   
    #[ORM\Column(options: ['default' => false])]
    private bool $pret_materiel = false;

    /**
    * Indique si le matériel a été restitué (true = rendu)
    */   
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $restitution_materiel = false;

    /**
     * Date à laquelle la commande est passée au statut "En attente du retour de matériel"
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateStatutRetourMateriel = null;

    // ==========================
    // Workflow pénalité
    // ==========================

    /**
     * Date à laquelle la commande est passée au statut "Livré"
    */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateStatutLivree = null;

    /**
     * Indique si le mail de pénalité a déjà été envoyé
    */   
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $mailPenaliteEnvoye = false;

    // ==========================
    // Relations ManyToOne
    // ==========================

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'utilisateur_id', nullable: false)] // ajout du nom de la colonne
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'menu_id', nullable: false)] // ajout du nom de la colonne
    private ?Menu $menu = null;

    /**
     * Indique si l'heure de livraison de la commande
    */ 
    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heure_livraison = null;

    /**
     * Indique le prix du menu de la commande
    */
    #[ORM\Column]
    private ?float $prix_menu = null;

    /**
     * Indique le nombre_personne de la commande
    */
    #[ORM\Column]
    private ?int $nombre_personne = null;

    /**
     * Indique le prix_livraison de la commande
    */
    #[ORM\Column]
    private ?float $prix_livraison = null;

    /**
     * Indique le motif_annulation de la commande
    */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motif_annulation = null;

    /**
     * Indique le montant_rembourse de la commande
    */
    #[ORM\Column(nullable: true)]
    private ?float $montant_rembourse = null;
    
    /**
     * Indique le adresse_livraison de la commande
    */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse_livraison = null;

    /**
     * Indique le ville_livraison de la commande
    */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville_livraison = null;

    /**
     * Indique le montant_acompte de la commande
    */
    #[ORM\Column(nullable: true)]
    private ?float $montant_acompte = null;

    // ==========================
    // Getters / Setters
    // ==========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroCommande(): ?string
    {
        return $this->numero_commande;
    }

    public function setNumeroCommande(string $numero_commande): static
    {
        $this->numero_commande = $numero_commande;
        return $this;
    }

    public function getDateCommande(): ?\DateTime
    {
        return $this->date_commande;
    }

    public function setDateCommande(\DateTime $date_commande): static
    {
        $this->date_commande = $date_commande;
        return $this;
    }

    public function getEtatMateriel(): string
    {
        // Cas impossible
        if ($this->pret_materiel === false && $this->restitution_materiel === true) {
            return 'INCOHERENT';
        }

        // (0,0) → terminé
        if ($this->pret_materiel === false && $this->restitution_materiel === false) {
            return 'TERMINEE';
        }

        // (1,1) → terminé
        if ($this->pret_materiel === true && $this->restitution_materiel === true) {
            return 'TERMINEE';
        }

        // (1,0) → attente retour
        return 'ATTENTE_RESTITUTION';
    }

    // Getter DateStatutRetourMateriel
    public function getDateStatutRetourMateriel(): ?\DateTimeInterface
    {
        return $this->dateStatutRetourMateriel;
    }

    // Setter DateStatutRetourMateriel
    public function setDateStatutRetourMateriel(\DateTimeInterface $date): self
    {
        $this->dateStatutRetourMateriel = $date;
        return $this;
    }

    public function getDatePrestation(): ?\DateTime
    {
        return $this->date_prestation;
    }

    public function setDatePrestation(\DateTime $date_prestation): static
    {
        $this->date_prestation = $date_prestation;
        return $this;
    }

    public function getHeureLivraison(): ?\DateTime
    {
        return $this->heure_livraison;
    }

    public function setHeureLivraison(\DateTime $heure_livraison): static
    {
        $this->heure_livraison = $heure_livraison;
        return $this;
    }

    public function getPrixMenu(): ?float
    {
        return $this->prix_menu;
    }

    public function setPrixMenu(float $prix_menu): static
    {
        $this->prix_menu = $prix_menu;
        return $this;
    }

    public function getNombrePersonne(): ?int
    {
        return $this->nombre_personne;
    }

    public function setNombrePersonne(int $nombre_personne): static
    {
        $this->nombre_personne = $nombre_personne;
        return $this;
    }

    public function getPrixLivraison(): ?float
    {
        return $this->prix_livraison;
    }

    public function setPrixLivraison(float $prix_livraison): static
    {
        $this->prix_livraison = $prix_livraison;
        return $this;
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

    public function isPretMateriel(): ?bool
    {
        return $this->pret_materiel;
    }

    public function setPretMateriel(bool $pret_materiel): static
    {
        $this->pret_materiel = $pret_materiel;
        return $this;
    }

    public function isRestitutionMateriel(): ?bool
    {
        return $this->restitution_materiel;
    }

    public function setRestitutionMateriel(bool $restitution_materiel): static
    {
        $this->restitution_materiel = $restitution_materiel;
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

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;
        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresse_livraison;
    }

    public function setAdresseLivraison(?string $adresse_livraison): static
    {
        $this->adresse_livraison = $adresse_livraison;
        return $this;
    }

    public function getVilleLivraison(): ?string
    {
        return $this->ville_livraison;
    }

    public function setVilleLivraison(?string $ville_livraison): static
    {
        $this->ville_livraison = $ville_livraison;
        return $this;
    }

    public function getMontantAcompte(): ?float
    {
        return $this->montant_acompte;
    }

    public function setMontantAcompte(?float $montant_acompte): static
    {
        $this->montant_acompte = $montant_acompte;
        return $this;
    }

    // Récupération du motif d'annulation des commandes
    public function getMotifAnnulation(): ?string
    {
        return $this->motif_annulation;
    }

    // Mise à jour du motif d'annulation des commandes
    public function setMotifAnnulation(?string $motif_annulation): static
    {
        $this->motif_annulation = $motif_annulation;
        return $this;
    }

    // Récupération du montant remboursé pour les commandes annulées
    public function getMontantRembourse(): ?float
    {
        return $this->montant_rembourse;
    }

    // Mise à jour du montant remboursé pour les commandes annulées
    public function setMontantRembourse(?float $montant_rembourse): static
    {
        $this->montant_rembourse = $montant_rembourse;
        return $this;
    }

    // ==========================
    // Date statut livrée
    // ==========================
    // Récupération de la date de livraison pour les commandes
    public function getDateStatutLivree(): ?\DateTimeInterface
    {
        return $this->dateStatutLivree;
    }

    // Mise à jour de la date de livraison pour les commandes
    public function setDateStatutLivree(\DateTimeInterface $date): self
    {
        $this->dateStatutLivree = $date;
        return $this;
    }

    // ==========================
    // Mail pénalité
    // ==========================

    // Récupération du flag d'envois du mail de pénalité
    // si mailPenaliteEnvoye==false -> mail pas encore envoyé
    // si mailPenaliteEnvoye==true -> mail déjà envoyé
    public function isMailPenaliteEnvoye(): bool
    {
        return $this->mailPenaliteEnvoye;
    }
    // Mise à jour du flag d'envois du mail de pénalité
    public function setMailPenaliteEnvoye(bool $value): self
    {
        $this->mailPenaliteEnvoye = $value;
        return $this;
    }
}