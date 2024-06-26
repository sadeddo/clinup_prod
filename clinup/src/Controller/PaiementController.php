<?php

namespace App\Controller;

use DateTime;
use Stripe\Stripe;
use Stripe\Account;
use App\Entity\User;
use App\Entity\Dispo;
use Stripe\AccountLink;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use App\Entity\Reservation;
use App\Service\EmailSender;
use Stripe\Checkout\Session;
use App\Entity\DemandeService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use App\Repository\NotificationsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaiementController extends AbstractController
{
    private $stripeSecretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->stripeSecretKey = $params->get('stripe_secret_key');
    }
    /*#[Route('/{idPresta}/{idDemande}/', name: 'checkouklt')]
    public function index($idPresta,$idDemande,UrlGeneratorInterface $generator,Security $security,EntityManagerInterface $entityManager,EmailSender $notificationService,NotificationService $notifService,ReservationRepository $reservationRepository,CommentPrestaRepository $commentPrestaRepository): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idDemande]);
        //details presta:
        $prestataire = $entityManager->getRepository(User::class)->findOneBy(['id' => $idPresta]);
        
        if (!$reservation || !$prestataire) {
            $this->addFlash('error', 'Réservation ou prestataire introuvable.');
            //return $this->redirectToRoute('app_reservation_index');
        }
       /*dd($presta);
        Stripe::setApiKey($this->stripeSecretKey);
        $duree = $reservation->getNbrHeure();
        $prixParHeure = $prestataire->getPrix(); // 30 euros par heure
        // Séparation des heures et des minutes
        $parts = explode("h", $duree);
        $heures = $parts[0];
        $minutes = $parts[1];

        // Convertir en minutes totales
        $minutesTotales = $heures * 60 + $minutes;

        // Calcul du coût
        // Convertissez les minutes en heures (car le prix est par heure)
        $heuresTotales = $minutesTotales / 60;

        // Calcul du coût total
        $cotTotal = $heuresTotales * $prixParHeure ;
        $amount = $cotTotal* 100; // 1000 centimes = 10 EUR
        $currency = 'eur';
        $intent = PaymentIntent::create([
            'amount' => '50',
            'currency' => $currency,
            'payment_method_types' => ['card'],
            'capture_method' => 'manual',
        ]);
        // Vérifier si l'intention de paiement a été créée avec succès
        if ($intent['id']) {
            $reservation->setStatut('confirmer');
        $reservation->setIdIntent($intent['id']);
        $reservation->setPrix($cotTotal);
        $reservation->setPrestataire($prestataire);
        $entityManager->persist($reservation);
        $entityManager->flush();
        //modif price et palier
        $notificationService->sendEmail(
            $prestataire->getEmail(),
            'Candidature Confirmée',
            'email/confirmPresta.html.twig',
            [
                'user' => $prestataire, // Objet ou tableau contenant les informations de l'utilisateur
            ]
        );
        $notifService->createNotification(
            $prestataire,
            'Candidature Confirmée',
            '/reservation/prestataire/consulter'

        );
        $numberOfMissions =  $reservationRepository->getNombreDeMissions($prestataire->getId());
        $average = $commentPrestaRepository->getAverageNoteForPrestataire($prestataire->getId());
        $tier = 'Bronze'; 
        $tarif = 35;
        if ($average >= 4 && $average < 4.5 && 34 >= 30 && $numberOfMissions >= 30 && $numberOfMissions < 70) {
            $tier = 'Argent';
            $tarif = 45;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 60;
        }
        $prestataire->setPrix($tarif);
        $entityManager->persist($prestataire);
        $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été confirmée avec succès!');
        } else {
            // Message d'échec
            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre demande de paiement. Veuillez réessayer.');
            return $this->redirectToRoute('checkout', ['idPresta' => $idPresta,'idDemande' => $idDemande], Response::HTTP_SEE_OTHER);
        }
        return $this->render('/index.html.twig', [
            'clientSecret' => $intent->client_secret,
            'paymentIntentId' => $intent->id,
            "user" => $security->getUser(),
            'demande' => $reservation,
            'presta' => $prestataire,
            'total' => $cotTotal,
            'IdPresta' => $idPresta,
            'IdDemande' => $idDemande
        ]);
    }*/
    #[Route('/paiement', name: 'app_paiement', methods: ['POST','GET'])]
    public function paiement(Security $security): Response
    {
        $user = $security->getUser();
        return $this->render('paiement/stripe.html.twig', [
            'user' => $user
        ]);
    }
    #[Route('/paiement/link', name: 'app_account_link', methods: ['POST','GET'])]
    public function accountLink(Security $security)
    {
        $accountId = $security->getUser()->getIdStripe();
        $stripe = Stripe::setApiKey($this->stripeSecretKey);
        $accountLink = AccountLink::create([
            'account' => $accountId,
            'refresh_url' => 'https://clinup.fr/paiement/refresh',
            'return_url' => 'https://clinup.fr/paiement/return',
            'type' => 'account_onboarding',
        ]);
        return new RedirectResponse($accountLink->url);
    }
    #[Route('/paiement/refresh', name: 'app_refresh_url', methods: ['POST','GET'])]
    public function refreshUrl(Security $security): Response
    {
        $this->addFlash('refresh', 'Il semble que le processus de configuration de votre compte de paiement n\'est pas encore terminé. Pour pouvoir recevoir des paiements, il est essentiel de finaliser ce processus. Veuillez cliquer sur le bouton ci-dessous pour retourner à la page de configuration et suivre les instructions pour compléter votre inscription sur Stripe.');
        $user = $security->getUser();
        return $this->render('paiement/stripe.html.twig', [
            'user' => $user
        ]);
    }
    #[Route('/paiement/return', name: 'app_return_url', methods: ['POST','GET'])]
    public function returnhUrl(Security $security,EntityManagerInterface $entityManager): Response
    {
        $this->addFlash('return', 'Félicitations, votre compte de paiement a été configuré avec succès !.');
        $user = $security->getUser();
        $user->setStatutStripe('1');
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->render('paiement/stripe.html.twig', [
            'user' => $user
        ]);
    }
}