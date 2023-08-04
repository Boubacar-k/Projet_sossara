<?php

namespace App\Repository;

use App\Entity\TypeImmo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeImmo>
 *
 * @method TypeImmo|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeImmo|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeImmo[]    findAll()
 * @method TypeImmo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeImmoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeImmo::class);
    }

//    /**
//     * @return TypeImmo[] Returns an array of TypeImmo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TypeImmo
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
