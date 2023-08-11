<?php

namespace App\Repository;

use App\Entity\PhotoReclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoReclamation>
 *
 * @method PhotoReclamation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoReclamation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoReclamation[]    findAll()
 * @method PhotoReclamation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoReclamation::class);
    }

//    /**
//     * @return PhotoReclamation[] Returns an array of PhotoReclamation objects
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

//    public function findOneBySomeField($value): ?PhotoReclamation
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
