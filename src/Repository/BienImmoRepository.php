<?php

namespace App\Repository;

use App\Entity\BienImmo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Commodite;

/**
 * @extends ServiceEntityRepository<BienImmo>
 *
 * @method BienImmo|null find($id, $lockMode = null, $lockVersion = null)
 * @method BienImmo|null findOneBy(array $criteria, array $orderBy = null)
 * @method BienImmo[]    findAll()
 * @method BienImmo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BienImmoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BienImmo::class);
    }

    public function save(BienImmo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BienImmo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBienByCommune (int $adressId,int $communeId){
        $qb = $this->createQueryBuilder('b');
        $qb->innerJoin('b.adresse','a')
        ->where('b.id = :adressId')
        ->andWhere($qb->expr()->eq('a.commune',':communeId'))
        ->setParameter('adressId', $adressId)
        ->setParameter('communeId', $communeId);

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return BienImmo[] Returns an array of BienImmo objects
//     */
   public function findByPiece(int $piece): array
   {
       return $this->createQueryBuilder('b')
           ->andWhere('b.nb_piece = :nb_piece')
           ->setParameter('nb_piece', $piece)
           ->orderBy('b.nb_piece', 'ASC')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult()
       ;
   }

//    public function findByStatut(string $statut): array
//    {

// //     createQueryBuilder('o')
// //    ->andWhere('o.statut LIKE :statut')
// //    ->setParameter('statut', '$statut%')
// //    ->getQuery()
// //    ->getResult()
//        return $this->
//        ;
//    }

   public function findByPrix(float $prix): array
   {
       return $this->createQueryBuilder('b')
           ->andWhere('b.prix = :prix')
           ->setParameter('prix', $prix)
           ->orderBy('b.prix', 'ASC')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult()
       ;
   }


//    /**
//     * @return BienImmo[] Returns an array of BienImmo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BienImmo
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
