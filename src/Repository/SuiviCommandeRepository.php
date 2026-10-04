<?php

namespace App\Repository;

use App\Entity\SuiviCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuiviCommande>
 */
class SuiviCommandeRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
			parent::__construct($registry, SuiviCommande::class);
	}

	//    /**
	//     * @return SuiviCommande[] Returns an array of SuiviCommande objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('s')
	//            ->andWhere('s.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('s.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?SuiviCommande
	//    {
	//        return $this->createQueryBuilder('s')
	//            ->andWhere('s.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }

	/**
	 * @description Suivis de plusieurs commandes en UNE seule requête, regroupés par id de commande
	 *   et triés du plus ancien au plus récent (même format que la route /suivi)
	 * @param int[] $commandeIds
	 * @return array<int, array<int, array{statut: string, date_statut: string}>>
	 */
	public function findFormatesParCommandes(array $commandeIds): array
	{
		if (!$commandeIds) {
			return [];
		}
		$suivis = $this->createQueryBuilder('s')
			->where('s.commande IN (:ids)')
			->setParameter('ids', $commandeIds)
			->orderBy('s.date_statut', 'ASC')
			->getQuery()
			->getResult();
		$parCommande = [];
		foreach ($suivis as $suivi) {
			$parCommande[$suivi->getCommande()->getId()][] = [
				'statut'      => $suivi->getStatut(),
				'date_statut' => $suivi->getDateStatut()->format('d/m/Y H:i'),
			];
		}
		return $parCommande;
	}
}
