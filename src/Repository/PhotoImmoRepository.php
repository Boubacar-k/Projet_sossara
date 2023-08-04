<?php

namespace App\Repository;

use App\Entity\PhotoImmo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoImmo>
 *
 * @method PhotoImmo|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoImmo|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoImmo[]    findAll()
 * @method PhotoImmo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoImmoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoImmo::class);
    }

//    /**
//     * @return PhotoImmo[] Returns an array of PhotoImmo objects
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

//    public function findOneBySomeField($value): ?PhotoImmo
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
