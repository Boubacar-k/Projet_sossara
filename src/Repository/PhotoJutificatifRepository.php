<?php

namespace App\Repository;

use App\Entity\PhotoJutificatif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoJutificatif>
 *
 * @method PhotoJutificatif|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoJutificatif|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoJutificatif[]    findAll()
 * @method PhotoJutificatif[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoJutificatifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoJutificatif::class);
    }

//    /**
//     * @return PhotoJutificatif[] Returns an array of PhotoJutificatif objects
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

//    public function findOneBySomeField($value): ?PhotoJutificatif
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
