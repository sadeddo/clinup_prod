<?php

namespace App\Controller;

use App\Entity\Probleme;
use App\Form\ProblemeType;
use App\Entity\Reservation;
use App\Repository\LogementRepository;
use App\Repository\ProblemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProblemeController extends AbstractController
{
    #[Route('/probleme', name: 'app_probleme_index', methods: ['GET'])]
    public function index(ProblemeRepository $problemeRepository,Security $security,LogementRepository $logementRepository): Response
    {
        $logements = $logementRepository->findBy(['hote' => $security->getUser()]);
        return $this->render('probleme/index.html.twig', [
            'logements' => $logements,
        ]);
    }

    #[Route('/problèmes/{id}/new', name: 'app_probleme_new', methods: ['GET', 'POST'])]
    public function new($id,Request $request, EntityManagerInterface $entityManager,ReservationRepository $reservationRepository): Response
    {
        $probleme = new Probleme();
        $form = $this->createForm(ProblemeType::class, $probleme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $probleme->setLogement($reservationRepository->findOneBy(['id' => $id])->getLogement());
            $probleme->setStatut('0');
            $entityManager->persist($probleme);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_prestataire', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('probleme/new.html.twig', [
            'probleme' => $probleme,
            'form' => $form,
        ]);
    }

    #[Route('/probleme/{id}', name: 'app_probleme_show', methods: ['GET'])]
    public function show(Probleme $probleme): Response
    {
        return $this->render('probleme/show.html.twig', [
            'probleme' => $probleme,
        ]);
    }

    #[Route('/probleme/{id}/edit', name: 'app_probleme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Probleme $probleme, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProblemeType::class, $probleme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_probleme_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('probleme/edit.html.twig', [
            'probleme' => $probleme,
            'form' => $form,
        ]);
    }

    #[Route('/probleme/{id}', name: 'app_probleme_delete', methods: ['POST'])]
    public function delete(Request $request, Probleme $probleme, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$probleme->getId(), $request->request->get('_token'))) {
            $entityManager->remove($probleme);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_probleme_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/probleme/{id}/statut', name: 'app_probleme_modifier_statut', methods: ['GET', 'POST'])]
    public function modifierStatut(Request $request, Probleme $probleme, EntityManagerInterface $entityManager): Response
    {
        if ($probleme->IsStatut() == '0' ||  $probleme->IsStatut() == NULL) {
            $probleme->setStatut('1');
            $entityManager->persist($probleme);
            $entityManager->flush();
        }elseif($probleme->IsStatut() == '1'){
            $probleme->setStatut('0');
            $entityManager->persist($probleme);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_probleme_index', [], Response::HTTP_SEE_OTHER);
    }
}
