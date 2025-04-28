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
        if ($logement->getAirbnb()) {
            $this->processReservationsForLink($logement, $logement->getAirbnb());
        }
        if ($logement->getBooking()) {
            $this->processReservationsForLink($logement, $logement->getBooking());
        }
    }
    $this->entityManager->flush();
}


    private function processReservationsForLink(Logement $logement, ?string $link): void
{
    if (!$link) {
        return;
    }

    $reservations = $this->icalService->getReservationsFromIcal($link);

    if (!is_iterable($reservations)) {
        return; // Stoppe si pas itérable
    }

    $isFromBooking = str_contains($link, 'booking.com');

    foreach ($reservations as $reservationData) {
        $summary = $reservationData['summary'] ?? '';

        if (!$isFromBooking && str_contains(strtolower($summary), 'not available')) {
            continue;
        }

        $start = new \DateTime($reservationData['start_time']);
        $end = new \DateTime($reservationData['end_time']);
        $uid = $reservationData['UID'] ?? null;

        if (!$uid) {
            continue; // Protection si UID absent
        }

        $existingReservation = $this->icalresRepository->findOneBy(['uid' => $uid]);

        if ($existingReservation) {
            $existingReservation->setDtStart($start);
            $existingReservation->setDtEnd($end);
        } else {
            $reservation = new Icalres();
            $reservation->setLogement($logement);
            $reservation->setDtStart($start);
            $reservation->setDtEnd($end);
            $reservation->setNbrHeure("1h30");
            $reservation->setPrix("35");
            $reservation->setHeure("11:30");
            $reservation->setStatut('0');
            $reservation->setUid($uid);
            $this->entityManager->persist($reservation);
        }
    }
}

    

    public function cleanOldReservations(Logement $logement, array $newReservations)
    {
        $existingReservations = $this->icalresRepository->findBy(['logement' => $logement]);

        foreach ($existingReservations as $existing) {
            $found = false;
            foreach ($newReservations as $reservation) {
                if ($existing->getDtStart()->format('Y-m-d H:i:s') === $reservation['start_time'] &&
                    $existing->getDtEnd()->format('Y-m-d H:i:s') === $reservation['end_time']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->entityManager->remove($existing);
            }
        }
        $this->entityManager->flush();
    }
}
