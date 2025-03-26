<?php

namespace App\Repository;

use App\Entity\Icalres;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Icalres>
 *
 * @method Icalres|null find($id, $lockMode = null, $lockVersion = null)
 * @method Icalres|null findOneBy(array $criteria, array $orderBy = null)
 * @method Icalres[]    findAll()
 * @method Icalres[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IcalresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Icalres::class);
    }

    //    /**
    //     * @return Icalres[] Returns an array of Icalres objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Icalres
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findUpcomingUnnotifiedReservations(\DateTimeInterface $from, \DateTimeInterface $to): array
{
    return $this->createQueryBuilder('r')
        ->join('r.logement', 'l')
        ->where('r.statut = :statut')
        ->andWhere('r.dtStart BETWEEN :from AND :to')
        ->setParameter('statut', 0)
        ->setParameter('from', $from)
        ->setParameter('to', $to)
        ->getQuery()
        ->getResult();
}

}
