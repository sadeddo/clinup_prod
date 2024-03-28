<?php

namespace App\Controller;

use App\Entity\Probleme;
use App\Form\ProblemeType;
use App\Entity\Reservation;
use App\Form\ProblemeLType;
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
    public function index(ProblemeRepository $problemeRepository,Security $security,LogementRepository $logementRepository,Request $request,EntityManagerInterface $entityManager): Response
    {
        $problemes =  $problemes = $problemeRepository->findProblemesByHoteId($security->getUser()->getId());
        return $this->render('probleme/index.html.twig', [
            'problemes' => $problemes,
            'cible' => ''
        ]);
    }
    #[Route('/probleme/ajouter', name: 'app_probleme_add', methods: ['GET', 'POST'])]
    public function addH(ProblemeRepository $problemeRepository,Security $security,LogementRepository $logementRepository,Request $request,EntityManagerInterface $entityManager): Response
    {
        $problemes =  $problemes = $problemeRepository->findProblemesByHoteId($security->getUser()->getId());

        $probleme = new Probleme();
        $form = $this->createForm(ProblemeLType::class, $probleme, [
            'user' => $security->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uniqueId = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $probleme->setNum($uniqueId);
            $probleme->setStatut('Non résolu');
            $entityManager->persist($probleme);
            $entityManager->flush();

            return $this->redirectToRoute('app_probleme_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('probleme/index.html.twig', [
            'problemes' => $problemes,
            'form' => $form,
            'cible' => 'add'
        ]);
    }

    #[Route('/problèmes/{id}/new', name: 'app_probleme_new', methods: ['GET', 'POST'])]
    public function new($id,Request $request,Security $security, EntityManagerInterface $entityManager,ReservationRepository $reservationRepository): Response
    {
        $prestataire = $security->getUser();
        $allDemandes = $reservationRepository->findReservationsByHote($prestataire->getId());
        $probleme = new Probleme();
        $form = $this->createForm(ProblemeType::class, $probleme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $probleme->setLogement($reservationRepository->findOneBy(['id' => $id])->getLogement());
            $uniqueId = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $probleme->setNum($uniqueId);
            $probleme->setStatut('Non résolu');
            $entityManager->persist($probleme);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_prestataire', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/reservationP.html.twig', [
            'probleme' => $probleme,
            'form' => $form,
            'cible' => 'addProb',
            'reservations' => $allDemandes
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
            $probleme->setStatut('1');
            $entityManager->persist($probleme);
            $entityManager->flush();

        return $this->redirectToRoute('app_probleme_index', [], Response::HTTP_SEE_OTHER);
    }
}
