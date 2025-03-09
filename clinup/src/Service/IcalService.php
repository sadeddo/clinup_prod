<?php
namespace App\Service;

use Sabre\VObject;

class IcalService {
    public function getReservationsFromIcal($icalUrl): array
    {
        // Vérifier si l'URL est valide
        $headers = @get_headers($icalUrl);
        if ($headers === false || strpos($headers[0], '200') === false) {
            return []; // Retourner un tableau vide si le lien n'est pas valide
        }

        // Récupérer le contenu iCal
        $icalContent = @file_get_contents($icalUrl);
        if ($icalContent === false) {
            return []; // Retourner un tableau vide si le contenu ne peut pas être récupéré
        }

        try {
            // Lire et parser le fichier iCal
            $vCalendar = VObject\Reader::read($icalContent);
        } catch (\Exception $e) {
            // En cas d'erreur de parsing, retourner un tableau vide
            return [];
        }

        $reservations = [];

        // Parcourir les événements VEVENT
        foreach ($vCalendar->VEVENT as $event) {
            // Vérifier la présence des propriétés essentielles
            if (!isset($event->SUMMARY, $event->DTSTART, $event->DTEND)) {
                continue; // Passer cet événement s'il manque des propriétés
            }

            // Extraire les valeurs de l'événement
            $summary = $event->SUMMARY->getValue();
            $startTime = $event->DTSTART->getDateTime()->format('Y-m-d');
            $endTime = $event->DTEND->getDateTime()->format('Y-m-d');
            $uid = $event->UID->getValue();

            // Ajouter la réservation à la liste
            $reservations[] = [
                'summary' => $summary,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'UID' => $uid
            ];
        }

        return $reservations;
    }
}
