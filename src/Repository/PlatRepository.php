<?php

namespace App\Repository;

use App\Entity\Plat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plat>
 */
class PlatRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
			parent::__construct($registry, Plat::class);
	}

	//    /**
	//     * @return Plat[] Returns an array of Plat objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('p')
	//            ->andWhere('p.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('p.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Plat
	//    {
	//        return $this->createQueryBuilder('p')
	//            ->andWhere('p.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }

	/**
	 * @description Tous les plats avec leurs allergènes en UNE seule requête SQL (au lieu d'une par plat)
	 * @return Plat[]
	 */
	public function findAllAvecAllergenes(): array
	{
		return $this->createQueryBuilder('p')
			->leftJoin('p.allergenes', 'a')->addSelect('a')
			->orderBy('p.id', 'ASC')
			->getQuery()
			->getResult();
	}
}
