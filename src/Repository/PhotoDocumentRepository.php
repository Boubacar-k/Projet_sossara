<?php

namespace App\Repository;

use App\Entity\PhotoDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoDocument>
 *
 * @method PhotoDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhotoDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhotoDocument[]    findAll()
 * @method PhotoDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotoDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoDocument::class);
    }

//    /**
//     * @return PhotoDocument[] Returns an array of PhotoDocument objects
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

//    public function findOneBySomeField($value): ?PhotoDocument
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
