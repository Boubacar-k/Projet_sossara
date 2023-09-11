<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }


//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

        public function findUsersOfRoles($roles)
        {
            $rsm = $this->createResultSetMappingBuilder('u');

            $rawQuery = sprintf(
                'SELECT %s
                        FROM public.user u
                        WHERE u.roles::jsonb ?? :role',
                $rsm->generateSelectClause()
            );

            $query = $this->getEntityManager()->createNativeQuery($rawQuery, $rsm);
            $query->setParameter('role', $roles);
            return $query->getResult();
        }

        public function findUsersWithParent()
        {
            return $this->createQueryBuilder('u')
                ->where('u.parent IS NOT NULL')
                ->getQuery()
                ->getResult();
        }

        // public function findByUserAgence($id){
        //     $qb = $this->getDoctrine()->getRepository('App\Entity\User')->createQueryBuilder();
        //     $qb->select('u')
        //         ->from('App\Entity\User', 'u')
        //         ->where('u.parent = :user')
        //         ->andWhere('u.age > 18')
        //         ->setParameter('user', $id);

        //         return $qb->getQuery()->getResult();
        // }

    // public function findUsersByRole($role): array
    // {
    //     $qb = $this->createQueryBuilder('u');
    //     $qb
    //         ->where($qb->expr()->orX(
    //             $qb->expr()->like('u.roles', ':roles'),
    //         ))
    //         ->setParameter('roles', '%"'.$role.'"%');

    //     return $qb->getQuery()->getResult();
    // }
}
