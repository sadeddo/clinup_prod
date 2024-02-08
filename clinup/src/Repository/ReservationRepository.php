<?php

namespace App\Repository;

use DateTime;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    private $em;
    public function __construct(ManagerRegistry $registry,EntityManagerInterface $em)
    {
        parent::__construct($registry, Reservation::class);
        $this->em = $em;
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
public function findReservationsByPrestataire($prestataireId)
{
    return $this->createQueryBuilder('r')
        ->join('r.logement', 'l')
        ->where('l.hote = :prestataireId')
        ->setParameter('prestataireId', $prestataireId)
        ->getQuery()
        ->getResult();
}
public function findReservationsByHote($prestataireId)
{
    return $this->createQueryBuilder('l')
        ->where('l.prestataire = :prestataireId')
        ->setParameter('prestataireId', $prestataireId)
        ->getQuery()
        ->getResult();
}
public function getNombreDeMissions(int $prestaId): int
    {
        return $this->createQueryBuilder('d')
        ->select('count(d.id)')
        ->where('d.prestataire = :prestaId')
        ->setParameter('prestaId', $prestaId)
        ->getQuery()
        ->getSingleScalarResult();

    }
    public function findMonthlyRevenuesForUser(int $prestaId)
    {
        return $this->createQueryBuilder('p')
            ->select('SUM(p.prix) as revenue', 'MONTH(p.date) as month')
            ->where('p.prestataire = :prestaId')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 'payer')
            ->setParameter('prestaId', $prestaId)
            ->groupBy('month')
            ->getQuery()
            ->getResult();
    }
    public function findAVGMonthlyRevenuesForUser(int $prestaId)
    {
        return $this->createQueryBuilder('p')
            ->select('ROUND(AVG(p.prix), 1) as revenue', 'MONTH(p.date) as month')
            ->where('p.prestataire = :prestaId')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 'payer')
            ->setParameter('prestaId', $prestaId)
            ->groupBy('month')
            ->getQuery()
            ->getResult();
    }

    public function countForCurrentMonth(int $prestaId)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m)')
            ->where('MONTH(m.date) = :currentMonth AND YEAR(m.date) = :currentYear')
            ->andWhere('m.prestataire = :prestaId')
            ->setParameters([
                'currentMonth' => date('m'),
                'currentYear' => date('Y')
            ])
            ->setParameter('prestaId', $prestaId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForToday(int $prestaId)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m)')
            ->where('m.date = :today')
            ->andWhere('m.prestataire = :prestaId')
            ->setParameter('today', new DateTime('today'))
            ->setParameter('prestaId', $prestaId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForCurrentWeek(int $prestaId)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m)')
            ->where('WEEK(m.date) = :currentWeek AND YEAR(m.date) = :currentYear')
            ->andWhere('m.prestataire = :prestaId')
            ->setParameters([
                'currentWeek' => date('W'),
                'currentYear' => date('Y')
            ])
            ->setParameter('prestaId', $prestaId)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function findAverageMonthlyRevenues()
    {
        return $this->createQueryBuilder('p')
            ->select('ROUND(AVG(p.prix), 1) as averages', 'MONTH(p.date) as month')
            ->where('p.statut = :statut')
            ->setParameter('statut', 'payer')
            ->groupBy('month')
            ->getQuery()
            ->getResult();
    }

    public function decimalHoursToTime($decimalHours) {
        $hours = floor($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);
        
        return sprintf("%dh%02d", $hours, $minutes);
    }
    
    public function TablerevenuesForUser(int $prestaId)
{
    $results = $this->createQueryBuilder('d')
        ->select('d.nbrHeure', 'd.prix', 'd.date')
        ->where('d.statut = :statut')
        ->andWhere('d.prestataire = :prestaId')
        ->setParameter('statut', 'payer')
        ->setParameter('prestaId', $prestaId)
        ->getQuery()
        ->getResult();

    $monthlyData = [];
    foreach ($results as $result) {
        $yearMonth = $result['date']->format('Y-m');
        if (!isset($monthlyData[$yearMonth])) {
            $monthlyData[$yearMonth] = ['totalHours' => 0, 'totalEarnings' => 0];
        }
        $timeParts = explode('h', $result['nbrHeure']);
        $hours = (int)$timeParts[0] + ((int)$timeParts[1] / 60);
        $monthlyData[$yearMonth]['totalHours'] += $hours;
        $monthlyData[$yearMonth]['totalEarnings'] += floatval($result['prix']); // Convertir 'prix' en float
    }

    // Convertir les heures décimales en format hh:mm
    foreach ($monthlyData as $month => $data) {
        $totalHours = $data['totalHours'];
        $hours = floor($totalHours);
        $minutes = round(($totalHours - $hours) * 60);

        $monthlyData[$month]['totalHours'] = sprintf("%dh%02d", $hours, $minutes);
    }

    return $monthlyData;
}

}
