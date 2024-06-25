<?php

namespace App\Controller;

use DateTime;
use App\Entity\Icalres;
use App\Form\IcalresType;
use App\Repository\IcalresRepository;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/icalres')]
class IcalresController extends AbstractController
{

    #[Route('/{id}/edit', name: 'app_icalres_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Icalres $icalre, EntityManagerInterface $entityManager, LogementRepository $logementRepository, Security $security,ReservationRepository $reservationRepository): Response
    {
        $form = $this->createForm(IcalresType::class, $icalre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Réservation mise à jour avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de la réservation');
            }

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
        $currentDate = new DateTime();
        $hote = $security->getUser();
        $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());
        $counts = [];
        foreach ($reservations as $reservation) {
            $counts[$reservation->getId()] = count($reservation->getPostulers());
        }
        return $this->render('reservation/index.html.twig', [
            'icalre' => $icalre,
            'logements' => $logementRepository->findBy(['hote' => $security->getUser()]),
            "cible" => "modifierIcal",
            'form' => $form,
            'now' => $currentDate->format('Y-m-d'),
            'reservations' => $reservations,
            'counts' => $counts,
        ]);
    }

    #[Route('/{id}', name: 'app_icalres_delete', methods: ['POST'])]
    public function delete(Request $request, Icalres $icalre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$icalre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($icalre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_icalres_index', [], Response::HTTP_SEE_OTHER);
    }
}
