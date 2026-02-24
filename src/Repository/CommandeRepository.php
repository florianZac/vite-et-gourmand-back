<?php

namespace App\Repository;

use App\Entity\Commande;
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
