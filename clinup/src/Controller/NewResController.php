<?php

namespace App\Controller;

use DateTime;
use Sabre\VObject;
use App\Entity\Icalres;
use App\Entity\Logement;
use App\Service\IcalService;
use App\Repository\IcalresRepository;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewResController extends AbstractController
{
    #[Route('/res', name: 'app_new_res')]
    public function index(LogementRepository $logementRepository, Security $security, IcalService $icalService, EntityManagerInterface $entityManager, IcalresRepository $reservationRepository): Response
    {
        $logements = $logementRepository->findBy(['hote' => $security->getUser()]);
        foreach ($logements as $logement) {
            if ($logement->getAirbnb()) {
                $this->processReservationsForLink($logement, $logement->getAirbnb(), $entityManager, $icalService, $reservationRepository);
            }
            if ($logement->getBooking()) {
                $this->processReservationsForLink($logement, $logement->getBooking(), $entityManager, $icalService, $reservationRepository);
            }
        }
        $entityManager->flush();
        
        return $this->render('new_res/index.html.twig', [
            'controller_name' => 'NewResController',
        ]);
    }
    
    private function processReservationsForLink(Logement $logement, ?string $link, EntityManagerInterface $entityManager, IcalService $icalService, IcalresRepository $reservationRepository): void
    {
            $reservations = $icalService->getReservationsFromIcal($link);
            foreach ($reservations as $reservationData) {
                $existingReservation = $reservationRepository->findOneBy([
                    'logement' => $logement,
                    'dtStart' => $reservationData['start_time'],
                    'dtEnd' => $reservationData['end_time'],
                ]);
                if (!$existingReservation) {
                    $reservation = new Icalres();
                    $reservation->setLogement($logement);
                    $reservation->setDtStart($reservationData['start_time']);
                    $reservation->setDtEnd($reservationData['end_time']);
                    $entityManager->persist($reservation);
                }
            }
    }
}
