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
public function index(
    Request $request,
    ReservationRepository $reservationRepository,
    LogementRepository $logementRepository,
    UserInterface $user,
    Security $security
): Response {
    $logementId = $request->query->get('logementId');
$logementId = $logementId !== '' ? (int) $logementId : null;

    $startDate = $request->query->get('startDate');
    $endDate = $request->query->get('endDate');
    $format = $request->query->get('format');

    if (in_array($format, ['csv', 'xlsx'])) {
        $reservationsDetails = $reservationRepository->findReservationDetailsForWeb($user, $logementId, $startDate, $endDate);
        return $this->exportData($reservationsDetails, $format);
    }

    $logementsData = $reservationRepository->countAndSumReservations($user, $logementId, $startDate, $endDate);
    $logements = $logementRepository->findBy(['hote' => $user]);
    $reservationsStats = $reservationRepository->countReservationsByStatus($user);

    return $this->render('design/test.html.twig', [
        'logementsData' => $logementsData,
        'logements' => $logements,
        'reservationsStats' => $reservationsStats
    ]);
}

private function exportData(array $reservations, string $format): StreamedResponse
{
    $filename = "export_" . date("Y_m_d") . "." . $format;

    $response = new StreamedResponse(function () use ($reservations) {
        $handle = fopen('php://output', 'w+');
        fputcsv($handle, ['Logement', 'Date', 'Heure', 'Statut', 'Prix (€)'], ';');

        foreach ($reservations as $r) {
            fputcsv($handle, [
                $r->getLogement()?->getNom(),
                $r->getDate()?->format('Y-m-d'),
                $r->getHeure()?->format('H:i'),
                self::formatReservationStatut($r->getStatut()),
                $r->getPrix()
            ], ';');
        }

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

    return $response;
}

private static function formatReservationStatut(?string $statut): string
{
    return match (strtolower($statut)) {
        'confirmer' => 'Confirmée',
        'payer'     => 'Payée',
        'annuler'   => 'Annulée',
        'en attente' => 'En attente',
        default     => ucfirst($statut ?? ''),
    };
}

    #[Route('/designAuth', name: 'app_design_auth')]
    public function auth(): Response
    {
        return $this->render('design/auth.html.twig', [
            'controller_name' => 'DesignController',
        ]);
    }
}
