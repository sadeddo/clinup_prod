<?php

namespace App\Controller;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FiscaleController extends AbstractController
{
    #[Route('/fiscale', name: 'app_fiscale')]
    public function index(EntityManagerInterface $entityManager,Security $security): Response
    {
        $presta = $security->getUser()->getId();
        //la somme des revenues par mois par user
        $revenueDataSum = $entityManager->getRepository(Reservation::class)->findMonthlyRevenuesForUser($presta);
        $monthlyRevenuesSum = [];
        foreach ($revenueDataSum as $data) {
            $monthlyRevenuesSum[$data['month']] = $data['revenue'];
        }
        //le moyen des revenues par mois par user
        $revenueDataAvg = $entityManager->getRepository(Reservation::class)->findAVGMonthlyRevenuesForUser($presta);
        $monthlyRevenuesAvg = [];
        foreach ($revenueDataAvg as $data) {
            $monthlyRevenuesAvg[$data['month']] = $data['revenue'];
        }

        //dd($revenueDataAvg);
        $countForCurrentMonth = $entityManager->getRepository(Reservation::class)->countForCurrentMonth($presta);
        //dd($countForCurrentMonth);
        $countForToday = $entityManager->getRepository(Reservation::class)->countForToday($presta);
        //dd($countForToday);
        $countForCurrentWeek = $entityManager->getRepository(Reservation::class)->countForCurrentWeek($presta);
        //dd($countForCurrentWeek);

        //revenue moyens:
        $averageRevenues = $entityManager->getRepository(Reservation::class)->findAverageMonthlyRevenues();
        // Transformer les données pour le graphique
        $dataForChart = [];

        foreach ($averageRevenues as $revenue) {
            $dataForChart[$revenue['month']] = $revenue['averages'];
        }
        //dd($dataForChart);

        //tableau fiscal
        $monthlyData = $entityManager->getRepository(Reservation::class)->TablerevenuesForUser($presta);
        return $this->render('fiscale/index.html.twig', [
            'monthlyRevenues' => $monthlyRevenuesSum,
            "month" => $countForCurrentMonth,
            "week" => $countForCurrentWeek,
            'today' => $countForToday,
            'dataForChartAll' => $dataForChart,
            'monthlyRevenuesAvg' => $monthlyRevenuesAvg,
            'monthlyData' => $monthlyData,
        ]);
    }
    #[Route('/fiscale/download-csv', name: 'download_csv')]
    public function downloadCsv(EntityManagerInterface $entityManager,Security $security)
    {
        $presta = $security->getUser()->getId();
        $monthlyData = $entityManager->getRepository(Reservation::class)->TablerevenuesForUser($presta);

        $response = new StreamedResponse(function() use ($monthlyData) {
            $handle = fopen('php://output', 'w+');

            // Ajouter l'en-tête du fichier CSV
            fputcsv($handle, ['Date', 'Nombre d\'Heures', 'Total'], ';');

            // Ajouter les données
            foreach ($monthlyData as $month => $data) {
                fputcsv($handle, [$month, $data['totalHours'], $data['totalEarnings'] . ' €'], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="revenus_mensuels.csv"');

        return $response;
    }

}
