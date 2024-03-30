<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Form\RepcommentType;
use App\Entity\CommentPresta;
use App\Form\CommentPrestaType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use App\Repository\NotificationsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\DemandeServiceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EvaluationController extends AbstractController
{
    #[Route('/evaluation', name: 'app_evaluation')]
    public function index(Security $security,CommentPrestaRepository $commentPrestaRepository,ReservationRepository $reservationRepository): Response
    {
        $idPresta = $security->getUser()->getId();
        $numberOfMissions =  $reservationRepository->getNombreDeMissions($idPresta);
        $average = $commentPrestaRepository->getAverageNoteForPrestataire($idPresta);

        $tier = 'Bronze'; 
        $tarif = 35;

        if ($average >= 4 && $average < 4.5 && 34 >= 30 && $numberOfMissions >= 30 && $numberOfMissions < 70) {
            $tier = 'Argent';
            $tarif = 45;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 60;
        }
        
        //les commentaire
        $comments = $commentPrestaRepository->findBy(['prestataire' => $idPresta]);
        return $this->render('evaluation/index.html.twig', [
            'controller_name' => 'EvaluationController',
            "user" => $security->getUser(),
            'evolution' => $average,
            'comments' => $comments,
            'palier' => $tier,
            'tarif' => $tarif,
            'cible' => '',
            'missions' =>  $numberOfMissions
        ]);
    }
    #[Route('/evaluation/{id}/reponse', name: 'app_rep')]
    public function new(Request $request,CommentPresta $commentPrestum,Security $security, $id,CommentPrestaRepository $commentPrestaRepository,ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $idPresta = $security->getUser()->getId();
        $numberOfMissions =  $reservationRepository->getNombreDeMissions($idPresta);
        $average = $commentPrestaRepository->getAverageNoteForPrestataire($idPresta);

        $tier = 'Bronze'; 
        $tarif = 35;

        if ($average >= 4 && $average < 4.5 && 34 >= 30 && $numberOfMissions >= 30 && $numberOfMissions < 70) {
            $tier = 'Argent';
            $tarif = 45;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 60;
        }

        //les commentaire
        $comments = $commentPrestaRepository->findBy(['prestataire' => $idPresta]);
        //rep
        $form = $this->createForm(RepcommentType::class, $commentPrestum);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            date_default_timezone_set('Europe/Paris');
            $commentPrestum->setReponse($form->get('reponse')->getData());
            $commentPrestum->setDateRep(new DateTimeImmutable());
            $entityManager->persist($commentPrestum);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réponse a été enregistrée avec succès!');
            return $this->redirectToRoute('app_evaluation', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('evaluation/index.html.twig', [
            'controller_name' => 'EvaluationController',
            "user" => $security->getUser(),
            'evolution' => $average,
            'comments' => $comments,
            'form' => $form,
            'cible' => 'rep',
            'palier' =>$tier,
            'tarif' => $tarif,
            'missions' =>  $numberOfMissions
        ]);
    }
}
