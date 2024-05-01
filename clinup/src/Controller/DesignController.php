<?php

namespace App\Controller;

use App\Repository\LogementRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DesignController extends AbstractController
{
    #[Route('/design', name: 'dashboard_logements')]
    public function index(Request $request,ReservationRepository $reservationRepository,LogementRepository $logementRepository, UserInterface $user,Security $security): Response
    {
    $logementId = $request->query->get('logementId');
    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');
    $format = $request->query->get('format');

    $logementsData = $reservationRepository->countAndSumReservations($user, $logementId, $startDate, $endDate);
    $logements = $logementRepository->findBy(['hote' => $user]);
    if ($format === 'csv' || $format === 'xlsx') {
        return $this->exportData($logementsData, $format);
    }
    $reservationsStats = $reservationRepository->countReservationsByStatus($user);
        return $this->render('design/test.html.twig', [
            'logementsData' => $logementsData,
            'logements' => $logements,
            'reservationsStats' => $reservationsStats
        ]);
    }
    private function exportData($data, $format)
{
    $filename = "export_".date("Y_m_d").".".$format;
    $response = new StreamedResponse(function() use ($data, $format) {
        $handle = fopen('php://output', 'w+');
        // Add header
        fputcsv($handle, ['Logement', 'Statut', 'Nombre de Réservations', 'Total Montant (€)'], ';');

        // Add data
        foreach ($data as $row) {
            fputcsv($handle, [$row['logementNom'], $row['reservationStatut'], $row['nombreReservations'], $row['totalMontant']], ';');
        }

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

    return $response;
}
    #[Route('/designAuth', name: 'app_design_auth')]
    public function auth(): Response
    {
        return $this->render('design/auth.html.twig', [
            'controller_name' => 'DesignController',
        ]);
    }
}
