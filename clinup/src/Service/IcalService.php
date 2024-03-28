<?php

namespace App\Service;

use Sabre\VObject;

class IcalService {
    public function getReservationsFromIcal($icalUrl)
    {
        $headers = @get_headers($icalUrl);
        if ($headers === false || strpos($headers[0], '200') === false) {
            return []; // Retourner un tableau vide si le lien n'est pas valide
        }
        $icalContent = file_get_contents($icalUrl);
        $vCalendar = VObject\Reader::read($icalContent);
        $reservations = [];

        foreach ($vCalendar->VEVENT as $event) {
            $summary = $event->SUMMARY->getValue();
            $startTime = $event->DTSTART->getDateTime()->format('Y-m-d H:i:s');
            $endTime = $event->DTEND->getDateTime()->format('Y-m-d H:i:s');

            $reservations[] = [
                'summary' => $summary,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        return $reservations;
    }
}