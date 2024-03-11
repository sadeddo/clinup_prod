<?php

namespace App\Repository;

use App\Entity\Probleme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Probleme>
 *
 * @method Probleme|null find($id, $lockMode = null, $lockVersion = null)
 * @method Probleme|null findOneBy(array $criteria, array $orderBy = null)
 * @method Probleme[]    findAll()
 * @method Probleme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProblemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Probleme::class);
    }

    public function findProblemesByHoteId($hoteId)
    {
    return $this->createQueryBuilder('p') // 'p' est un alias pour l'entité Probleme
        ->innerJoin('p.logement', 'l') // Joindre la table Logement via la relation définie dans l'entité
        ->innerJoin('l.hote', 'h') // Joindre la table Hote via la relation définie dans Logement
        ->where('h.id = :hoteId') // Condition pour filtrer par hoteId
        ->setParameter('hoteId', $hoteId)
        ->getQuery()
        ->getResult();
    }

//    /**
//     * @return Probleme[] Returns an array of Probleme objects
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

//    public function findOneBySomeField($value): ?Probleme
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
