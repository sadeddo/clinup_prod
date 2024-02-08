<?php

namespace App\Repository;

use App\Entity\Justif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Justif>
 *
 * @method Justif|null find($id, $lockMode = null, $lockVersion = null)
 * @method Justif|null findOneBy(array $criteria, array $orderBy = null)
 * @method Justif[]    findAll()
 * @method Justif[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JustifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Justif::class);
    }

//    /**
//     * @return Justif[] Returns an array of Justif objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Justif
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
