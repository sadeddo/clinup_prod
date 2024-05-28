<?php
namespace App\Controller;

use App\Service\PdfGenerator;
use App\Service\ReceiptPdfGenerator;
use App\Repository\ReceiptRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReceiptController extends AbstractController
{
    private $pdfGenerator;
    private $receiptRepository;

    public function __construct(PdfGenerator $pdfGenerator, ReceiptRepository $receiptRepository)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->receiptRepository = $receiptRepository;
    }

    #[Route('/generate-receipt/{id}', name: 'generate_receipt')]
    public function generateReceipt(int $id): Response
    {
        // Retrieve the receipt from the database using the ID
        $receipt = $this->receiptRepository->findOneBy(['reservation' => $id]);

        if (!$receipt) {
            throw $this->createNotFoundException('The receipt does not exist');
        }

        // Prepare receipt data
        $receiptData = [
            'receiptNumber' => $receipt->getNumber(),
            'amount' => $receipt->getReservation()->getPrix(),
            'paymentDate' => $receipt->getPaymentDate(),
        ];

        // Generate PDF
        $pdfContent = $this->pdfGenerator->generatePdf($receiptData);

        // Create and return the response to download the PDF
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="receipt_' . $receipt->getNumber() . '.pdf"',
            ]
        );
    }
}
