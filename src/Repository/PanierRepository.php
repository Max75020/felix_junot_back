<?php

namespace App\Repository;

use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Panier::class);
	}

	/**
	 * Trouver le panier ouvert pour un utilisateur spécifique
	 *
	 * @param int $userId L'ID de l'utilisateur
	 * @return Panier|null Le panier ouvert, ou null s'il n'existe pas
	 */
	public function findOpenCartByUserId(int $userId): ?Panier
	{
		return $this->createQueryBuilder('p')
			->andWhere('p.utilisateur = :userId')
			->andWhere('p.etat = :etat')
			->setParameter('userId', $userId)
			->setParameter('etat', 'ouvert')
			->getQuery()
			->getOneOrNullResult();
	}

	//    /**
	//     * @return Panier[] Returns an array of Panier objects
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

	//    public function findOneBySomeField($value): ?Panier
	//    {
	//        return $this->createQueryBuilder('p')
	//            ->andWhere('p.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
