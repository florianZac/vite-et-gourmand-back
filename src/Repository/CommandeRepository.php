<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * @author Florian Aizac
     * @created 24/02/2026
     * @description fonction personalisé responsable des requêtes SQL sur la table commande.
     *  il  récupère toutes les données d'un utilisateur triées par date décroissante.
     * @param Utilisateur $utilisateur L'utilisateur dont on veut récupérer les commandes
     * @return Commande[] Tableau d'objets Commande
    */
    public function findByUtilisateur($utilisateur): array
    {
        // 1. : Créer un QueryBuilder pour construire la requête SQL en utilisant l'alias 'c' pour la table Commande
        // 1.1:  équivalent à "SELECT * FROM commande c"
        // 2. : Ajouter une condition pour filtrer les commandes par utilisateur avec la variable utilisateur passé en parametre
        // 2.1: équivalent à "WHERE c.utilisateur = :utilisateur
        // 3  : Remplissage des données de la variable utilisateur dans la requete SQL  
        // 3.1: équivalent à "WHERE commande.utilisateur_id = 3"
        // 4 : Trier les valeurs par ordre décroissant par date décroissante
        // 4.1: équivalent à "ORDER BY c.date DESC" ASC pour ordre croissant
        // 5 : transforme la requete SQL construite en une requete exécutable
        // 5.1 : équivalent à "EXECUTE SELECT * FROM commande c WHERE c.utilisateur = 3 ORDER BY c.date DESC"
        // 6 : Exécute la requete et retourne les résultats sous forme d'un tableau d'objets Commande
        // 6.1 : équivalent à "GET ALL"
        return $this->createQueryBuilder('c')
            ->andWhere('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.date_commande', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Récupère toutes les commandes actuellement en cours.
     * Sont exclues :
     *  - les commandes avec le statut == "Terminée"
     *  - les commandes avec le statut == "Annulée"
     *
     * Les commandes sont triées par date de commande les plus récentes en premier
     * @return array Liste des commandes en cours
     */
    public function findCommandesEnCours(): array
    {
        return $this->createQueryBuilder('c')
            // Exclut les commandes terminées ou annulées
            ->where('c.statut NOT IN (:statut)')
            ->setParameter('statut', ['Terminée', 'Annulée'])
            // Trie par date de commande décroissante
            ->orderBy('c.date_commande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des commandes par numéro de commande.
     * sur le champ numero_commande (ex : CMD-001, CMD-002, etc.).
     * @param string $nom Chaîne de caractères à rechercher dans le numéro de commande
     * @return array Liste des commandes correspondant à la recherche
     */
    public function findByNumeroCommande(string $nom): array
    {
        return $this->createQueryBuilder('c')
            // Recherche partielle sur le numéro de commande
            ->where('c.numero_commande LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fonction de récupération des statistique pour l'affiche avec Chart.js en front
     * @return array les données
     */
    public function getStatistiques(): array
    {
        // Récupère toutes les commandes terminées
        $commandes = $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'Terminée')
            ->getQuery()
            ->getResult();

        // Calcul chiffre d'affaire total
        $chiffreAffaire = 0;
        foreach ($commandes as $commande) {
            $chiffreAffaire += $commande->getPrixMenu() + $commande->getPrixLivraison();
        }

        // Calcul nombre total commandes
        $nombreCommandes = count($commandes);

        // Calcul revenu moyen
        $revenuMoyen = $nombreCommandes > 0 ? $chiffreAffaire / $nombreCommandes : 0;

        // Calcul ventes par mois
        $ventesParMois = [];
        foreach ($commandes as $commande) {
            $mois = $commande->getDateCommande()->format('m/Y');
            
            if (!isset($ventesParMois[$mois])) {
                $ventesParMois[$mois] = [
                    'mois'             => $mois,
                    'total_commandes'  => 0,
                    'chiffre_affaire'  => 0,
                    'menus'            => []
                ];
            }

            $ventesParMois[$mois]['total_commandes']++;
            $ventesParMois[$mois]['chiffre_affaire'] += $commande->getPrixMenu() + $commande->getPrixLivraison();
            
            // Compte les menus pour trouver le plus prisé
            $nomMenu = $commande->getMenu() ? $commande->getMenu()->getTitre() : 'Inconnu';
            if (!isset($ventesParMois[$mois]['menus'][$nomMenu])) {
                $ventesParMois[$mois]['menus'][$nomMenu] = 0;
            }
            $ventesParMois[$mois]['menus'][$nomMenu]++;
        }

        // Trouve le menu le plus prisé par mois
        foreach ($ventesParMois as &$moisData) {
            arsort($moisData['menus']);
            $moisData['menu_plus_prise'] = array_key_first($moisData['menus']);
            unset($moisData['menus']);
        }

        // Trie par mois
        ksort($ventesParMois);

        return [
            'chiffre_affaire_total' => round($chiffreAffaire, 2),
            'nombre_commandes'      => $nombreCommandes,
            'revenu_moyen'          => round($revenuMoyen, 2),
            'ventes_par_mois'       => array_values($ventesParMois)
        ];
    }


    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
