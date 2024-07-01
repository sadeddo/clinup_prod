<?php
namespace App\Service;

use App\Entity\Logement;
use App\Entity\Icalres;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\IcalresRepository;
use App\Repository\LogementRepository;

class IcalSynchronizationService
{
    private $entityManager;
    private $icalService;
    private $logementRepository;
    private $icalresRepository;

    public function __construct(EntityManagerInterface $entityManager, IcalService $icalService, LogementRepository $logementRepository, IcalresRepository $icalresRepository)
    {
        $this->entityManager = $entityManager;
        $this->icalService = $icalService;
        $this->logementRepository = $logementRepository;
        $this->icalresRepository = $icalresRepository;
    }

    public function synchronize(): void
    {
        $logements = $this->logementRepository->findAll();
        foreach ($logements as $logement) {
            // Synchronisation pour Airbnb
            if ($logement->getAirbnb()) {
                $this->processReservationsForLink($logement, $logement->getAirbnb());
            }
            // Synchronisation pour Booking
            if ($logement->getBooking()) {
                $this->processReservationsForLink($logement, $logement->getBooking());
            }
        }
        $this->entityManager->flush();
    }

    private function processReservationsForLink(Logement $logement, ?string $link): void
    {
        $reservations = $this->icalService->getReservationsFromIcal($link);
        foreach ($reservations as $reservationData) {
            $existingReservation = $this->icalresRepository->findOneBy([
                'logement' => $logement,
                'dtStart' => $reservationData['start_time'],
                'dtEnd' => $reservationData['end_time'],
            ]);
            if (!$existingReservation) {
                $reservation = new Icalres();
                $reservation->setLogement($logement);
                $reservation->setDtStart($reservationData['start_time']);
                $reservation->setDtEnd($reservationData['end_time']);
                $reservation->setNbrHeure("1h30");
                $reservation->setPrix("35");
                $reservation->setStatut('0');
                $this->entityManager->persist($reservation);
            }
        }
    }
}
