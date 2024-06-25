<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Invit;
use DateTimeImmutable;
use App\Form\InvitType;
use App\Entity\Reservation;
use App\Service\EmailSender;
use App\Form\ReservationType;
use App\Repository\InvitRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvitController extends AbstractController
{
    #[Route('/invit', name: 'app_invit', methods: ['GET' , 'POST'])]
    public function index(Security $security,InvitRepository $invitRepository): Response
    {
        $hote = $security->getUser();
        $invites = $invitRepository->findBy(['hote' => $hote]);
        return $this->render('invit/index.html.twig', [
            'invites' => $invites,
            'cible' => '',
        ]);
    }

    #[Route('/invit/ajouter', name: 'app_invit_add', methods: ['GET' , 'POST'])]
    public function add(Security $security,InvitRepository $invitRepository,Request $request, EntityManagerInterface $entityManager, EmailSender $notificationService): Response
    {
        $hote = $security->getUser();
        $invites = $invitRepository->findBy(['hote' => $hote]);
        $invit = new Invit();
        $form = $this->createForm(InvitType::class, $invit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            date_default_timezone_set('Europe/Paris');
            $invit->setCode(sprintf('%06d', rand(0, 999999)));
            $invit->setHote($hote);
            $date = new DateTimeImmutable();
            $invit->setDate($date->format('Y-m-d'));
            $invit->setEtat('en attente');
            $entityManager->persist($invit);
            $entityManager->flush();

            $notificationService->sendEmail(
                $invit->getEmail(),
                ' '.  $hote->getFirstname() .' vous a invité(e) à rejoindre Clinup ',
                'email/invit.html.twig',
                [
                    'hote' => $hote,
                    'presta' => $invit->getNom(),
                    'invit' => $invit,
                ]
            );
            $this->addFlash('success', 'Votre invitation à été envoyer avec succès !');
            return $this->redirectToRoute('app_invit');
        }
        return $this->render('invit/index.html.twig', [
            'invites' => $invites,
            'form' => $form,
            'cible' => 'ajouter',
        ]);
    }

    private function sendNotification(User $prestataire, EmailSender $notificationService, NotificationService $notifService, Reservation $reservation): void {
        // Logique d'envoi d'e-mail
        $notificationService->sendEmail(
            $prestataire->getEmail(),
            'Nouvelle Réservation Disponible',
            'email/nvReservation.html.twig',
            [
                'user' => $prestataire, 
            ]
        );
        // Logique de création de notification
        $notifContent = 'Une nouvelle opportunité de réservation correspondant à vos disponibilités et votre zone géographique vient d\'apparaître sur notre plateforme.';
        $notifService->createNotification(
            $prestataire,
            $notifContent,
            '/reservation/prestataire/'.$reservation->getId().'/consulter'

        );
    }
    #[Route('/invit/{id}/reserver', name: 'app_invit_reserver', methods: ['GET' , 'POST'])]
    public function resever(User $user,Security $security,InvitRepository $invitRepository,Request $request, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService): Response
    {
        $hote = $security->getUser();
        $invites = $invitRepository->findBy(['hote' => $hote]);

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'user' => $hote,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $reservation->setStatut("en attente");
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->sendNotification($user, $notificationService, $notifService, $reservation);
            $this->addFlash('success', 'Votre demande a été envoyée avec succès ! Vous pouvez suivre l\'avancement de votre réservation sur la page "Réservations".');
            return $this->redirectToRoute('app_invit');
        }
        return $this->render('invit/index.html.twig', [
            'invites' => $invites,
            'form' => $form,
            'cible' => 'reserver',
        ]);
    }
    
    #[Route('/invit/{id}/relance', name: 'app_invit_relance', methods: ['GET' , 'POST'])]
    public function relancer(Invit $invit,Security $security, EmailSender $notificationService): Response
    {
        $hote = $security->getUser();
        $notificationService->sendEmail(
            $invit->getEmail(),
            ' '.  $hote->getFirstname() .'vous a invité(e) à rejoindre Clinup ',
            'email/invit.html.twig',
            [
                'hote' => $hote,
                'presta' => $invit->getNom(),
                'invit' => $invit,
            ]
        );

        $this->addFlash('success', 'Votre invitation à été renvoyer avec succès !');
        return $this->redirectToRoute('app_invit');
        
    }

    #[Route('/invit/{id}/supprimer', name: 'app_invit_supprimer', methods: ['GET' , 'POST'])]
    public function supprimer(Invit $invit, Security $security, EmailSender $notificationService, EntityManagerInterface $entityManager): Response
    {
        // Suppression de l'invitation
        $entityManager->remove($invit);
        $entityManager->flush();
    
        // Ajout d'un message flash pour informer l'utilisateur
        $this->addFlash('success', 'Votre relation avec ce prestataire a été supprimée avec succès !');
    
        // Redirection vers la liste des invitations
        return $this->redirectToRoute('app_invit');
    }
    

    
}
