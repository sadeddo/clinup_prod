<?php

namespace App\Repository;

use App\Entity\ImgTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImgTask>
 *
 * @method ImgTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImgTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImgTask[]    findAll()
 * @method ImgTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImgTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImgTask::class);
    }

//    /**
//     * @return ImgTask[] Returns an array of ImgTask objects
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

//    public function findOneBySomeField($value): ?ImgTask
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
