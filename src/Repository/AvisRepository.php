<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 * 
 *  1. findLastPublishedAvis() : Récupère les derniers avis publiés avec le statut = "Publié"
 *  2. findAvis()              : Récupère les avis filtrés, ou filtrés par statut    
 *  3. updateStatut()          : Met à jour le statut d'un avis
 *  4. removeAvis()            : Supprime un avis
 *  5. saveAvis() :            : Sauvegarde un avis en base
 * 
 */
class AvisRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Avis::class);
	}

	/**
	 * @description Récupère les derniers avis publiés avec le statut = "Publié")
	 * @param int $limit Nombre maximum d'avis à récupérer
	 * @return Avis[]
	 */
	public function findLastPublishedAvis(int $limit = 5): array
	{
		return $this->createQueryBuilder('a')
			// Auteur chargé dans la même requête (évite une requête par avis)
			->leftJoin('a.utilisateur', 'u')->addSelect('u')
			// On ignore les espaces avant/après et on ignore la casse
			->where('LOWER(TRIM(a.statut)) = :statut')
			->setParameter('statut', strtolower('publié'))
			->orderBy('a.date', 'DESC')  // On prend les plus récents par date
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

	/**
	 * @description Récupère les avis filtrés, ou filtrés par statut
	 *
	 * @param string|null $statut "Publié", "En attente"
	 * @param int|null $limit Nombre maximum d'avis à retourner (ex: 5 derniers)
	 * @return Avis[]
	 */
	public function findAvis(?string $statut = null, ?int $limit = null): array
	{
		$qb = $this->createQueryBuilder('a')
								->orderBy('a.date', 'DESC'); // Les plus récents en premier

		if ($statut) {
			$qb->andWhere('a.statut = :statut')
					->setParameter('statut', $statut);
		}

		if ($limit) {
			$qb->setMaxResults($limit);
		}
		return $qb->getQuery()->getResult();
	}

	/**
	 * @description Met à jour le statut d'un avis
	 *
	 * @param Avis $avis
	 * @param string $statut Nouveau statut
	 * @return void
	 */
	public function updateStatut(Avis $avis, string $statut): void
	{
    $em = $this->getEntityManager();
    $avis->setStatut($statut);
		$em->flush();
	}

	/**
	 * @description Supprime un avis
	 *
	 * @param Avis $avis
	 * @return void
	 */
	public function removeAvis(Avis $avis, bool $flush = true): void
	{
    $em = $this->getEntityManager(); // récupère l'EntityManager
		$em->remove($avis); // prépare la suppression
    if($flush) {
      $em->flush();
    }
	}

  /**
   * @description sauvegarde un avis en base
   *
   * @param Avis $avis L'entité Avis à sauvegarder
   * @param bool $flush Si vrai, flush immédiat en base
   */
	public function saveAvis(Avis $avis, bool $flush = true): void
	{
    $em = $this->getEntityManager(); // récupère l'EntityManager
    $em->persist($avis); // prépare l'entité
		if ($flush) {
			$em->flush(); // applique les changements en base
		}
	}


	//    /**
	//     * @return Avis[] Returns an array of Avis objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('a')
	//            ->andWhere('a.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('a.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Avis
	//    {
	//        return $this->createQueryBuilder('a')
	//            ->andWhere('a.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }

	/**
	 * @description Avis avec client et commande chargés dans la même requête SQL (au lieu de 2 par avis)
	 * @param string|null $statut Filtre facultatif sur le statut (ex : en_attente)
	 * @return Avis[]
	 */
	public function findAvecRelations(?string $statut = null): array
	{
		$qb = $this->createQueryBuilder('a')
			->leftJoin('a.utilisateur', 'u')->addSelect('u')
			->leftJoin('a.commande', 'c')->addSelect('c')
			->orderBy('a.id', 'ASC');
		if ($statut !== null) {
			$qb->andWhere('a.statut = :statut')->setParameter('statut', $statut);
		}
		return $qb->getQuery()->getResult();
	}

	/**
	 * @description Avis d'un client indexés par id de commande (une seule requête pour toutes ses commandes)
	 * @return array<int, Avis>
	 */
	public function findParCommandePourUtilisateur($utilisateur): array
	{
		$avis = $this->createQueryBuilder('a')
			->where('a.utilisateur = :u')
			->setParameter('u', $utilisateur)
			->getQuery()
			->getResult();
		$index = [];
		foreach ($avis as $a) {
			$index[$a->getCommande()->getId()] = $a;
		}
		return $index;
	}
}
