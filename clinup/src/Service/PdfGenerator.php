<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use DateTimeImmutable;
use App\Entity\Receipt;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;

class PdfGenerator
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generatePdf($receipt): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $html = $this->twig->render('receipt/index.html.twig', [
            'receipt' => $receipt
        ]);

        $dompdf->loadHtml($html);
        $customPaperSize = array(0, 0, 470, 630);
        $dompdf->setPaper($customPaperSize);
        $dompdf->render();

        return $dompdf->output();
    }
    public function createReceipt(int $reservationId,EntityManagerInterface $entityManager)
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $reservationId]);
        date_default_timezone_set('Europe/Paris');
        $dateTimeImmutable = new DateTimeImmutable();
        $dateString = $dateTimeImmutable->format('Y-m-d H:i');

        $receipt = new Receipt();
        $receipt->setNumber($this->generateReceiptNumber());
        $receipt->setReservation($reservation);

        $receipt->setPaymentDate($dateString);

        $entityManager->persist($receipt);
        $entityManager->flush();
    }

    private function generateReceiptNumber(): string
    {
        $number = random_int(1, 9999);
        return  $number;
    }
}
 