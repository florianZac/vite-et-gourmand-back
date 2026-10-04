<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
			parent::__construct($registry, Menu::class);
	}

	//    /**
	//     * @return Menu[] Returns an array of Menu objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('m')
	//            ->andWhere('m.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('m.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Menu
	//    {
	//        return $this->createQueryBuilder('m')
	//            ->andWhere('m.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }

	/**
	 * @description Tous les menus avec thème, régime, plats, allergènes et tags en UNE seule requête SQL
	 * (évite le problème "N+1" : avant, Doctrine faisait une requête par menu, par plat et par liste de tags)
	 * @return Menu[]
	 */
	public function findAllAvecDetails(): array
	{
		return $this->createQueryBuilder('m')
			->leftJoin('m.theme', 'th')->addSelect('th')
			->leftJoin('m.regime', 'r')->addSelect('r')
			->leftJoin('m.plats', 'p')->addSelect('p')
			->leftJoin('p.allergenes', 'a')->addSelect('a')
			->leftJoin('m.tags', 't')->addSelect('t')
			->orderBy('m.id', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * @description Un menu avec thème, régime, plats, allergènes et tags en UNE seule requête SQL
	 */
	public function findAvecDetails(int $id): ?Menu
	{
		return $this->createQueryBuilder('m')
			->leftJoin('m.theme', 'th')->addSelect('th')
			->leftJoin('m.regime', 'r')->addSelect('r')
			->leftJoin('m.plats', 'p')->addSelect('p')
			->leftJoin('p.allergenes', 'a')->addSelect('a')
			->leftJoin('m.tags', 't')->addSelect('t')
			->where('m.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getOneOrNullResult();
	}
}
