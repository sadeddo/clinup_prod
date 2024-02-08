<?php

namespace App\Repository;

use App\Entity\CommentPresta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentPresta>
 *
 * @method CommentPresta|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommentPresta|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommentPresta[]    findAll()
 * @method CommentPresta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentPrestaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentPresta::class);
    }
    public function getAverageNoteForPrestataire($idPrestataire)
    {
        return $this->createQueryBuilder('e')
            ->select('AVG(e.evaluation) as avg_note')
            ->where('e.prestataire = :idPrestataire')
            ->setParameter('idPrestataire', $idPrestataire)
            ->getQuery()
            ->getSingleScalarResult();
    }
//    /**
//     * @return CommentPresta[] Returns an array of CommentPresta objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CommentPresta
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
