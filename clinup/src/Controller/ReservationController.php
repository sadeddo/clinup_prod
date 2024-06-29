<?php

namespace App\Controller;

use DateTime;
use DateTimeZone;
use Stripe\Stripe;
use App\Entity\User;
use Stripe\Transfer;
use App\Entity\Dispo;
use App\Entity\Invit;
use App\Entity\Notif;
use DateTimeImmutable;
use DateTimeInterface;
use App\Entity\Icalres;
use App\Entity\Logement;
use App\Entity\Postuler;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use App\Form\PostulerType;
use App\Entity\Reservation;
use App\Form\PostInvitType;
use App\Service\EmailSender;
use App\Service\IcalService;
use App\Form\ReservationType;
use App\Service\PdfGenerator;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Service\NotificationService;
use App\Repository\IcalresRepository;
use App\Repository\LogementRepository;
use App\Repository\PostulerRepository;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    public function __construct(ParameterBagInterface $params)
    {
        $this->stripeSecretKey = $params->get('stripe_secret_key');
        $this->mapsSecretKey = $params->get('maps_secret_key');
    }
    #[Route('/hote', name: 'app_reservation_index', methods: ['GET'])]
    public function index(Security $security, ReservationRepository $reservationRepository, IcalService $icalService, IcalresRepository $icalresRepository,LogementRepository $logementRepository,EntityManagerInterface $entityManager,Request $request): Response
    {
        if ($request->query->get('payment') === 'success') {
            // Ajoutez un message flash de succès
            $this->addFlash('success', 'Votre réservation a été confirmée avec succès.');
        }
        $hote = $security->getUser();
        $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());

        $counts = [];
        foreach ($reservations as $reservation) {
            $counts[$reservation->getId()] = count($reservation->getPostulers());
        }
        $logements = $logementRepository->findBy(['hote' => $security->getUser()]);
        foreach ($logements as $logement) {
            if ($logement->getAirbnb()) {
                $this->processReservationsForLink($logement, $logement->getAirbnb(), $entityManager, $icalService, $icalresRepository);
            }
        }
        $entityManager->flush();
        $currentDate = new \DateTime();
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'counts' => $counts,
            'cible' => '',
            'now' => $currentDate->format('Y-m-d'),
            'logements' => $logements
        ]);
    }
    //enregistrer la reservation
    private function createReservation(Reservation $reservation, EntityManagerInterface $entityManager, Security $security): void {
        $reservation->setStatut("en attente");
        $reservation->setPrix("0");
        $entityManager->persist($reservation);
        $entityManager->flush();
    }
    //envoyer notif
    private function notifyPrestataire($dispo, Reservation $reservation, EmailSender $notificationService, NotificationService $notifService): void {
        $distance = $this->getDistance($reservation->getLogement()->getAdresse(), $dispo->getPrestataire()->getAdresse(), "K");
        if ($distance <= 40) {
            $this->sendNotification($dispo->getPrestataire(), $notificationService, $notifService, $reservation);
        }
    }
    //notif
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
    //dispo
    private function getAvailablePrestataires(Reservation $reservation, EntityManagerInterface $entityManager): array {
        //calculer la date de fin
        $dateDebut = $reservation->getHeure()->format('H:i:s');
        $dateDebut  = DateTime::createFromFormat('H:i:s', $dateDebut);
        $duree = $reservation->getNbrHeure();
        preg_match("/(\d+)h(\d+)/", $duree, $matches);
        $heures = (int) $matches[1];
        $minutes = (int) $matches[2];
        $dateDebut->modify("+$heures hours");
        $dateDebut->modify("+$minutes minutes");
        $heureFin = $dateDebut->format('H:i:s');
        $dispos = $entityManager->getRepository(Dispo::class)->createQueryBuilder('d')
            ->join('d.prestataire', 'p')
            ->Where('d.date = :date')
            ->andWhere('d.dtStart <= :timeDebut AND d.dtEnd >= :timeFin')
            ->setParameter('date', $reservation->getDate())
            ->andWhere('p.isVerified = 1')
            ->setParameter('timeDebut', $reservation->getHeure()->format('H:i:s'))
            ->setParameter('timeFin', $heureFin)
            ->getQuery()
            ->getResult();
        return $dispos;
        
    }
    private function processReservationsForLink(Logement $logement, ?string $link, EntityManagerInterface $entityManager, IcalService $icalService, IcalresRepository $reservationRepository): void
    {
            $reservations = $icalService->getReservationsFromIcal($link);
            foreach ($reservations as $reservationData) {
                $existingReservation = $reservationRepository->findOneBy([
                    'logement' => $logement,
                    'dtStart' => $reservationData['start_time'],
                    'dtEnd' => $reservationData['end_time'],
                ]);
                if (!$existingReservation) {
                    $reservation = new Icalres();
                    $reservation->setLogement($logement);
                    $reservation->setDtStart($reservationData['start_time']);
                    $reservation->setDtEnd($reservationData['end_time']);
                    $reservation->setNbrHeure("1h30");
                    $reservation->setPrix("35");
                    $reservation->setStatut('0');
                    $entityManager->persist($reservation);
                }
            }
    }
    #[Route('/hote/{id}/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new($id,Request $request,EntityManagerInterface $entityManager,Security $security, EmailSender $notificationService,NotificationService $notifService, ReservationRepository $reservationRepository, IcalService $icalService, IcalresRepository $icalresRepository,LogementRepository $logementRepository,UserRepository $UserRepository): Response
    {

        $logements = $logementRepository->findBy(['hote' => $security->getUser()]);
        $res = $icalresRepository->findOneBy(['id' => $id]);
        $reservation = new Reservation();
        $reservation->setLogement($res->getLogement());
        $reservation->setDate(new DateTime($res->getDtEnd()));
        $reservation->setHeure(new DateTime('11:00:00'));
        if ($res->getNbrHeure() === null) {
            $reservation->setNbrHeure('1h30');
        }else{
            $reservation->setNbrHeure($res->getNbrHeure());
        }
        $reservation->setStatut("en attente");
        if ($res->getPrix() === null) {
            $reservation->setPrix('35');
        }else{
            $reservation->setPrix($res->getPrix());
        }
        $entityManager->persist($reservation);
        $res->setStatut('1');
        $entityManager->persist($res);
        $entityManager->flush();
        // Envoyer d'abord aux prestataires invités
        $invits = $security->getUser()->getInvits();
        $notified = false;  // Flag to check if any invited prestataire was notified
        foreach ($invits as $invit) {
            $prestataireId = $invit->getPresta();
            if ($prestataireId) {
                $prestataire = $UserRepository->findOneBy(['id' => $prestataireId]);
                if ($prestataire) {
                    $this->sendNotification($UserRepository->findOneBy(['id' => $invit->getPresta()]), $notificationService, $notifService, $reservation);
                    $notified = true;  // Set flag to true as we have notified at least one prestataire
                }
            }
        }

        // If no invited prestataire was notified, proceed to notify available prestataires
        if (!$notified) {
            $dispos = $this->getAvailablePrestataires($reservation, $entityManager);
            foreach($dispos as $dispo){
                $this->notifyPrestataire($dispo, $reservation, $notificationService, $notifService);
            }
        }
        $this->addFlash('success', 'Votre demande a été envoyée avec succès ! Vous pouvez suivre l\'avancement de votre réservation sur cette page');
         return $this->redirectToRoute('app_list_postuler', ['id'=> $reservation->getId()], Response::HTTP_SEE_OTHER);
        $hote = $security->getUser();
        $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());
        $counts = [];
        foreach ($reservations as $reservation) {
            $counts[$reservation->getId()] = count($reservation->getPostulers());
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'logements' => $logements,
            'form' => $form,
            'cible' => '',
            'counts' => $counts,
        ]);
    }

    #[Route('/prestataire/{id}/consulter', name: 'app_reservation_show', methods: ['GET','POST'])]
    public function show(Reservation $reservation,EntityManagerInterface $entityManager,Security $security,VideoRepository $videoRepository): Response
    {
        //verifier si l'utilisateur à deja postuler
        $postulation = $entityManager->getRepository(Postuler::class)
                    ->findOneBy(['reservation' => $reservation, 'prestataire' => $security->getUser()]);
        
        $hoteId = $reservation->getLogement()->getHote()->getId();
        $prestataireId = $security->getUser()->getId();
        $video = $videoRepository->findOneBy(['reservation' => $reservation]);
        $invitExists = $entityManager->getRepository(Invit::class)->existsInvit($hoteId, $prestataireId);
        return $this->render('reservation/showP.html.twig', [
            'reservation' => $reservation,
            'cible' => '',
            'hasApplied' => $postulation,
            'invitExists' => $invitExists,
            'video' => $video
        ]);
    }
    //comment postuler
    #[Route('/prestataire/{id}/postuler', name: 'app_reservation_postuler', methods: ['GET','POST'])]
    public function postuler($id,Security $security,Request $request,Reservation $reservation, EntityManagerInterface $entityManager,EmailSender $notificationService,NotificationService $notifService,VideoRepository $videoRepository): Response
    {
        $comment = new Postuler();
        $form = $this->createForm(PostulerType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPrestataire($security->getUser());
            $comment->setReservation($reservation);
            $entityManager->persist($comment);
            $entityManager->flush();
            $currentDate = new \DateTime();
            $notificationService->sendEmail(
                $reservation->getLogement()->getHote()->getEmail(),
                'Nouvelle Candidature pour Votre Réservation le'.' '.$currentDate->format('d-m-Y'),
                'email/prestaPostule.html.twig',
                [
                    'user' => $reservation->getLogement()->getHote(), // Objet ou tableau contenant les informations de l'utilisateur
                    
                ]
            );
            $notifService->createNotification(
                $reservation->getLogement()->getHote(),
                'Nouvelle Candidature pour Votre Réservation le'.' '.$currentDate->format('d-m-Y'),
                '/reservation/hote/'.$reservation->getId().'/postulers'
    
            );
            $this->addFlash('success', 'Votre candidature a été envoyée avec succès ! Vous recevrez une notification dès que l\'hôte acceptera votre demande.');
            return $this->redirectToRoute('app_reservation_show', ['id' => $id], Response::HTTP_SEE_OTHER);
        }
           //verifier si l'utilisateur à deja postuler
        $postulation = $entityManager->getRepository(Postuler::class)
        ->findOneBy(['reservation' => $reservation, 'prestataire' => $security->getUser()]);
        //if invit
        $hoteId = $reservation->getLogement()->getHote()->getId();
        $prestataireId = $security->getUser()->getId();
        $invitExists = $entityManager->getRepository(Invit::class)->existsInvit($hoteId, $prestataireId);
        $video = $videoRepository->findOneBy(['reservation' => $reservation]);
        return $this->render('reservation/showP.html.twig', [
            'comment' => $comment,
            'form' => $form,
            'reservation' => $reservation,
            'cible' => 'postuler',
            'hasApplied' => $postulation,
            'invitExists' => $invitExists,
            'video' => $video
        ]);
    }
    //comment postuler par invit (choix)
    #[Route('/invit/{id}/postuler', name: 'app_reservation_postuler_invit', methods: ['GET','POST'])]
    public function postulerInvit($id,Security $security,Request $request,Reservation $reservation, EntityManagerInterface $entityManager,EmailSender $notificationService,NotificationService $notifService,ReservationRepository $reservationRepository, CommentPrestaRepository $commentPrestaRepository,VideoRepository $videoRepository): Response
    {
        $comment = new Postuler();
        $form = $this->createForm(PostInvitType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            ///les modiif
            $rep = $form->getData()->getComment();
            if ($rep == 'Je ne suis pas disponible') {
                $dispos = $this->getAvailablePrestataires($reservation, $entityManager);
                foreach($dispos as $dispo){
                    $this->notifyPrestataire($dispo, $reservation, $notificationService, $notifService);
                }
                $date = new \DateTime('now', new DateTimeZone('Europe/Paris'));
                $comment->setCreatedAt($date);
                $comment->setPrestataire($security->getUser());
                $comment->setReservation($reservation);
                $entityManager->persist($comment);
                $entityManager->flush();
                $currentDate = new \DateTime();
                $notificationService->sendEmail(
                    $reservation->getLogement()->getHote()->getEmail(),
                    'Nouvelle réponse pour Votre Réservation le'.' '.$currentDate->format('d-m-Y'),
                    'email/prestaPostule.html.twig',
                    [
                        'user' => $reservation->getLogement()->getHote(), // Objet ou tableau contenant les informations de l'utilisateur
                        
                    ]
                );
                $notifService->createNotification(
                    $reservation->getLogement()->getHote(),
                    'Nouvelle réponse pour Votre Réservation le'.' '.$currentDate->format('d-m-Y'),
                    '/reservation/hote/'.$reservation->getId().'/postulers'
        
                );
                $this->addFlash('success', 'Votre réponse a été envoyée avec succès !');
            }elseif ($rep == 'Je suis disponible') {
                $prestataire = $security->getUser();
                $reservation->setStatut('confirmer');
                $reservation->setIdIntent('invit');
                $reservation->setPrestataire($security->getUser());
                $entityManager->persist($reservation);
                $entityManager->flush();
                //mail
                $currentDate = new \DateTime();
                $notificationService->sendEmail(
                    $reservation->getLogement()->getHote()->getEmail(),
                    'Réservation confirmée par votre prestataire le'.' '.$currentDate->format('d-m-Y'),
                    'email/confirmeInvit.html.twig',
                    [
                        'user' => $reservation->getLogement()->getHote(), // Objet ou tableau contenant les informations de l'utilisateur
                        
                    ]
                );
                $notifService->createNotification(
                    $reservation->getLogement()->getHote(),
                    'Réservation confirmée par votre prestataire le'.' '.$currentDate->format('d-m-Y'),
                    '/reservation/hote/'.$reservation->getId().'/postulers'
        
                );
                $numberOfMissions =  $reservationRepository->getNombreDeMissions($prestataire->getId());
                $average = $commentPrestaRepository->getAverageNoteForPrestataire($prestataire->getId());
                $tier = 'Bronze'; 
                $tarif = 35;
                if ($average >= 4 && $average < 4.5 && 34 >= 30 && $numberOfMissions >= 30 && $numberOfMissions < 70) {
                    $tier = 'Argent';
                    $tarif = 40;
                } elseif ($average > 4.5 && $numberOfMissions > 70) {
                    $tier = 'Or';
                    $tarif = 45;
                }
                $prestataire->setPrix($tarif);
                $entityManager->persist($prestataire);
                $entityManager->flush();
                $this->addFlash('success', 'Réservation confirmée avec succès !');
            }
            
            return $this->redirectToRoute('app_reservation_show', ['id' => $id], Response::HTTP_SEE_OTHER);
        }
           //verifier si l'utilisateur à deja postuler
        $postulation = $entityManager->getRepository(Postuler::class)
        ->findOneBy(['reservation' => $reservation, 'prestataire' => $security->getUser()]);
        //if invit
        $hoteId = $reservation->getLogement()->getHote()->getId();
        $prestataireId = $security->getUser()->getId();
        $invitExists = $entityManager->getRepository(Invit::class)->existsInvit($hoteId, $prestataireId);
        $video = $videoRepository->findOneBy(['reservation' => $reservation]);
        return $this->render('reservation/showP.html.twig', [
            'comment' => $comment,
            'form' => $form,
            'reservation' => $reservation,
            'cible' => 'postulerInvit',
            'hasApplied' => $postulation,
            'invitExists' => $invitExists,
            'video' => $video
        ]);
    }
    #[Route('/hote/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/hote/{id}/delete', name: 'app_reservation_delete', methods: ['POST','GET'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
            $entityManager->remove($reservation);
            $entityManager->flush();
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    //calculer distance
    function getDistance($addressFrom, $addressTo, $unit = ''){
        // Google API key
        $apiKey = $this->mapsSecretKey;
        // Change address format
        $formattedAddrFrom    = str_replace(' ', '+', $addressFrom);
        $formattedAddrTo     = str_replace(' ', '+', $addressTo);
        
        // Geocoding API request with start address
        $geocodeFrom = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($formattedAddrFrom).'&sensor=false&key='.$apiKey);
        $outputFrom = json_decode($geocodeFrom);
        if(!empty($outputFrom->error_message)){
            return $outputFrom->error_message;
        }
        
        // Geocoding API request with end address
        $geocodeTo = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($formattedAddrTo).'&sensor=false&key='.$apiKey);
        $outputTo = json_decode($geocodeTo);
        if(!empty($outputTo->error_message)){
            return $outputTo->error_message;
        }
        
        // Get latitude and longitude from the geodata
        $latitudeFrom    = $outputFrom->results[0]->geometry->location->lat;
        $longitudeFrom    = $outputFrom->results[0]->geometry->location->lng;
        $latitudeTo        = $outputTo->results[0]->geometry->location->lat;
        $longitudeTo    = $outputTo->results[0]->geometry->location->lng;
        
        // Calculate distance between latitude and longitude
        $theta    = $longitudeFrom - $longitudeTo;
        $dist    = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
        $dist    = acos($dist);
        $dist    = rad2deg($dist);
        $miles    = $dist * 60 * 1.1515;
        
        // Convert unit and return distance
        $unit = strtoupper($unit);
        if($unit == "K"){
            return round($miles * 1.609344, 2).' km';
        }elseif($unit == "M"){
            return round($miles * 1609.344, 2).' meters';
        }else{
            return round($miles, 2).' miles';
        }
    }
    //chercher prestataires
    #[Route('/prestataires', name: 'app_list_prestataire')]
    public function findPrest(EntityManagerInterface $entityManager,Request $request, SessionInterface $session,EntityManagerInterface $em,Security $security,Reservation $reservation){
        
        
    }
    //la liste des personnes postuler
    #[Route('/hote/{id}/postulers', name: 'app_list_postuler')]
    public function consulter(Reservation $reservation,PostulerRepository $PostulerRepository,CommentPrestaRepository $commentPrestaRepository, VideoRepository $videoRepository)
    {
        $video = $videoRepository->findOneBy(['reservation' => $reservation]);
        return $this->render('reservation/postuler.html.twig', [
            'reservation' => $reservation,
            'video' => $video,
            
        ]);
    }
    
    //pour la liste des reservation  pour prestataire:
    #[Route('/prestataire/consulter', name: 'app_reservation_prestataire', methods: ['GET'])]
    public function indexP(Security $security, ReservationRepository $reservationRepository): Response
    {
        $prestataire = $security->getUser();
        $allDemandes = $reservationRepository->findReservationsByHote($prestataire->getId());
        return $this->render('reservation/reservationP.html.twig', [
            'reservations' => $allDemandes,
            'cible' => ''
        ]);
    }
    //la liste des personne postuler pour une reservation:
    #[Route('/hote/{id}/choisir', name: 'app_reservation_choisir', methods: ['GET'])]
    public function choisir($id,Security $security, PostulerRepository $postulerRepository,VideoRepository $videoRepository): Response
    {
        $prestataire = $security->getUser();
        $comments = $postulerRepository->findBy(['reservation' => $id]);
        $video = $videoRepository->findOneBy(['reservation' => $reservation]);
        return $this->render('reservation/postuler.html.twig', [
            'prestataires' => $comments,
            'video' => $video
        ]);
    }
    //confirmation prestataire
    #[Route('/hote/{idIntent}/{idPrestataire}/{idReservation}/confirmer', name: 'app_reservation_confirmer', methods: ['GET'])]
    public function confirmer($idIntent,$idPrestataire,$idReservation,Security $security, EntityManagerInterface $entityManager,CommentPrestaRepository $commentPrestaRepository,ReservationRepository $reservationRepository,EmailSender $notificationService,NotificationService $notifService): Response
    {
        //trouver le prestataire
        $prestataire = $entityManager->getRepository(User::class)->findOneBy(['id' => $idPrestataire]);
        //trouver la reservation
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idReservation]);
        //dd($reservation);
       //calculer la date de fin
       $dateDebut = $reservation->getHeure()->format('H:i:s');
       $dateDebut  = DateTime::createFromFormat('H:i:s', $dateDebut);
       $duree = $reservation->getNbrHeure();
       preg_match("/(\d+)h(\d+)/", $duree, $matches);
       $heures = (int) $matches[1];
       $minutes = (int) $matches[2];
       $dateDebut->modify("+$heures hours");
       $dateDebut->modify("+$minutes minutes");
       $heureFin = $dateDebut->format('H:i:s');
       $dispo = $entityManager->getRepository(Dispo::class)->createQueryBuilder('d')
           ->Where('d.date = :date')
           ->andWhere('d.dtStart <= :timeDebut AND d.dtEnd >= :timeFin')
           ->andWhere('d.prestataire = :prestataire')
           ->setParameter('date', $reservation->getDate())
           ->setParameter('timeDebut', $reservation->getHeure()->format('H:i:s'))
           ->setParameter('timeFin', $heureFin)
           ->setParameter('prestataire', $prestataire->getId())
           ->getQuery()
           ->getOneOrNullResult();
        if ($dispo) {
        if ($dispo->getDtStart() < $reservation->getHeure()) {
            $newDispoAvant = new Dispo();
            $newDispoAvant->setDtStart($dispo->getDtStart());
            $newDispoAvant->setDtEnd($reservation->getHeure()->format('H:i:s'));
            $newDispoAvant->setPrestataire($prestataire);
            $newDispoAvant->setStatut('dispo');
            $newDispoAvant->SetDate($reservation->getDate());
            $entityManager->persist($newDispoAvant);
            $entityManager->flush();
        }
        if ($heureFin < $dispo->getDtEnd()) {
            $newDispoApres = new Dispo();
            $newDispoApres->setDtStart($heureFin);
            $newDispoApres->setDtEnd($dispo->getDtEnd());
            $newDispoApres->setPrestataire($prestataire);
            $newDispoApres->setStatut('dispo');
            $newDispoApres->SetDate($reservation->getDate());
            $entityManager->persist($newDispoApres);
            $entityManager->flush();
        }
    
        // Calcul du coût total
        $cotTotal = $prestataire->getPrix(); 
        $amount = $cotTotal* 100; // 1000 centimes = 10 EUR
        $currency = 'eur';
        $entityManager->remove($dispo);
        $reservation->setStatut('confirmer');
        $reservation->setIdIntent($idIntent);
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
            $tarif = 40;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 45;
        }
        $prestataire->setPrix($tarif);
        $entityManager->persist($prestataire);
        $entityManager->flush();

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/hote/{id}/valider', name: 'app_reservation_valider', methods: ['POST','GET'])]
    public function valider(Request $request, int $id, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService,PdfGenerator $pdfGenerator): Response
    {
        // Récupérer l'entité Reservation par son ID
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Aucune réservation trouvée pour l\'id '.$id);
        }
        $stripe = new StripeClient($this->stripeSecretKey);
        try {
            
            // Effectuer le transfert des fonds vers le compte Stripe du prestataire
            $transfer = $stripe->transfers->create([
                'amount' => $reservation->getPrix() * 100 * 0.95,
                'currency' => 'eur',
                'destination' => $reservation->getPrestataire()->getIdStripe(),
                'source_transaction' => $reservation->getIdIntent(),
              ]);
                // Mettre à jour le statut de la réservation
                $reservation->setStatut('payer');
                $entityManager->persist($reservation);
                
                // Suppression des tâches liées si nécessaire
                $tasks = $reservation->getLogement()->getTasks();
                foreach ($tasks as $task) {
                    $imgTasks = $task->getImgTasks();
                    foreach ($imgTasks as $imgTask) {
                        $entityManager->remove($imgTask);
                    }
                }
                $entityManager->flush();
                
                //reçu
                $pdfGenerator->createReceipt($reservation->getId(),$entityManager);
                // Envoyer les notifications par email et sur la plateforme
                $notificationService->sendEmail(
                    $reservation->getPrestataire()->getEmail(),
                    'Prestation Validée',
                    'email/validePrestation.html.twig',
                    ['user' => $reservation->getPrestataire()]
                );
    
                $notifService->createNotification(
                    $reservation->getPrestataire(),
                    'Prestation Validée',
                    '/reservation/prestataire/consulter'
                );
                
                // Redirection vers la page d'index avec message de succès
                $this->addFlash('success', 'La prestation a été validée et le paiement transféré avec succès.');
                return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Gestion des erreurs Stripe
            $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Exception $e) {
            // Gestion des autres erreurs
            $this->addFlash('error', 'Erreurr: ' . $e->getMessage());
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
    }  
    
    #[Route('/payment-success-invit', name: 'app_reservation_valider_payer', methods: ['POST','GET'])]
public function payerInvit(Request $request, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository, EmailSender $notificationService, NotificationService $notifService): Response
{
    Stripe::setApiKey($this->stripeSecretKey);
    $data = json_decode($request->getContent(), true);

    if (!$data || !isset($data['paymentIntentId'], $data['reservationId'], $data['prestataireId'], $data['cotTotal'])) {
        $this->addFlash('error', 'Données de paiement manquantes ou invalides');
        return $this->redirectToRoute('app_reservation_index');
    }

    $paymentIntentId = $data['paymentIntentId'];
    $reservation = $reservationRepository->find($data['reservationId']);

    if (!$reservation) {
        $this->addFlash('error', 'Réservation non trouvée');
        return $this->redirectToRoute('app_reservation_index');
    }

    try {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

        if ($paymentIntent->status !== 'succeeded') {
            $this->addFlash('error', 'Le paiement n\'a pas réussi ou est encore en attente');
            return $this->redirectToRoute('app_reservation_index');
        }

        $montantCentimes = $data['cotTotal'] * 100;
        $transfer = Stripe::transfers()->create([
            'amount' => $montantCentimes,
            'currency' => 'eur',
            'destination' => $reservation->getPrestataire()->getIdStripe(),
        ]);

        if ($transfer->status !== 'succeeded') {
            throw new \Exception("Échec du transfert des fonds.");
        }

        $reservation->setStatut('payé');
        $reservation->setIdIntent($paymentIntentId);
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Suppression des tâches liées et notifications
        $this->finalizeReservation($reservation, $entityManager, $notificationService, $notifService);

        $this->addFlash('success', 'La prestation a été validée et le paiement transféré avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $this->addFlash('error', 'Erreur Stripe: ' . $e->getMessage());
        return $this->redirectToRoute('app_reservation_index');
    } catch (\Exception $e) {
        $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        return $this->redirectToRoute('app_reservation_index');
    }
}

private function finalizeReservation(Reservation $reservation, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService)
{
    $tasks = $reservation->getLogement()->getTasks();
    foreach ($tasks as $task) {
        foreach ($task->getImgTasks() as $imgTask) {
            $entityManager->remove($imgTask);
        }
    }
    $entityManager->flush();

    $notificationService->sendEmail(
        $reservation->getPrestataire()->getEmail(),
        'Prestation Validée',
        'email/validePrestation.html.twig',
        ['user' => $reservation->getPrestataire()]
    );

    $notifService->createNotification(
        $reservation->getPrestataire(),
        'Prestation Validée',
        '/reservation/prestataire/consulter'
    );
}


    // annuler la réservation
#[Route('/hote/{id}/annuler', name: 'app_reservation_annuler', methods: ['POST','GET'])]
public function annuler(Request $request, Reservation $reservation, EntityManagerInterface $entityManager, EmailSender $emailService, NotificationService $notifService): Response
{
    $idIntent = $reservation->getIdIntent();
    $stripe = new \Stripe\StripeClient($this->stripeSecretKey);

    try {
        // Vérifier si l'identifiant de l'intent n'est pas nul et n'est pas égal à 'invit'
        if ($idIntent && $idIntent != 'invit') {
            try {
                // Faire un remboursement partiel de 10 euros (1000 centimes)
                $refund = $stripe->refunds->create([
                    'charge' => $idIntent, // Utilisez 'charge' au lieu de 'payment_intent'
                    'amount' => 1000, // Montant en centimes
                ]);
                // Ajouter un message flash pour le remboursement
                $this->addFlash('success', 'Vous allez bientôt recevoir un remboursement partiel de 10 euros.');
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Gestion des erreurs de l'API Stripe
                $this->addFlash('error', 'Erreur lors du remboursement : ' . $e->getMessage());
                return $this->redirectToRoute('app_reservation_index');
            }
        }

        // Modifier le statut de la réservation
        $reservation->setStatut('Annuler');
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Envoyer un email au prestataire pour l'informer
        $emailService->sendEmail(
            $reservation->getPrestataire()->getEmail(),
            'Réservation Annulée',
            'email/AnnulPresta.html.twig',
            [
                'user' => $reservation->getPrestataire(),
            ]
        );

        // Envoyer une notification sur l'application
        $notifService->createNotification(
            $reservation->getPrestataire(),
            'Réservation Annulée',
            '/reservation/prestataire/consulter'
        );

        // Ajouter un message flash pour l'annulation
        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    } catch (\Exception $e) {
        // Gestion des erreurs générales
        $this->addFlash('error', 'Une erreur inattendue est survenue : ' . $e->getMessage());
        return $this->redirectToRoute('app_reservation_index');
    }
}


    //annuler reservation (en attente)
    #[Route('/hote/{id}/annulerA', name: 'app_reservation_annulerAtt', methods: ['POST','GET'])]
    public function annulerAtt(Request $request, Reservation $reservation, EntityManagerInterface $entityManager, EmailSender $emailService,NotificationService $notifService): Response
    {
        $reservation->setStatut('Annuler');
        $entityManager->persist($reservation);
        $entityManager->flush();
        $this->addFlash('success','Votre réservation a été annulée avec succès.');
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    //annuler la reservationP
    #[Route('/prestataire/{id}/annulerP', name: 'app_reservation_annulerP', methods: ['POST','GET'])]
    public function annulerP(Request $request, Reservation $reservation, EntityManagerInterface $entityManager, EmailSender $notificationService,NotificationService $notifService): Response
    {
            $reservation->setStatut('Annuler');
            $entityManager->persist($reservation);
            $entityManager->flush();
            $notificationService->sendEmail(
                $reservation->getLogement()->getHote()->getEmail(),
                'Réservation Annulée',
                'email/annulHote.html.twig',
                [
                    'user' => $reservation->getLogement()->getHote(), 
                ]
            );
            $notifService->createNotification(
                $reservation->getLogement()->getHote(),
                'Réservation Annulée',
                '/reservation/hote'
    
            );
        return $this->redirectToRoute('app_reservation_prestataire', [], Response::HTTP_SEE_OTHER);
    }
    //demande de presta simple
    #[Route('/hote/ajouter', name: 'app_reservation_ajouter', methods: ['GET', 'POST'])]
    public function ajoiuter(Request $request,EntityManagerInterface $entityManager,Security $security, EmailSender $notificationService,NotificationService $notifService, ReservationRepository $reservationRepository, IcalService $icalService, IcalresRepository $icalresRepository,LogementRepository $logementRepository,UserRepository $UserRepository): Response
    {

        $logements = $logementRepository->findBy(['hote' => $security->getUser()]);
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'user' => $security->getUser(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setStatut("en attente");
            $entityManager->persist($reservation);
            $entityManager->flush();
            // Envoyer d'abord aux prestataires invités
            $invits = $security->getUser()->getInvits();
            $notified = false;  // Flag to check if any invited prestataire was notified
            foreach ($invits as $invit) {
                if ($invit->getPresta()) {
                    $this->sendNotification($UserRepository->findOneBy(['id' => $invit->getPresta()]), $notificationService, $notifService, $reservation);
                    $notified = true;
                }
            }
            if (!$notified) {
                $dispos = $this->getAvailablePrestataires($reservation, $entityManager);
                foreach($dispos as $dispo){
                    $this->notifyPrestataire($dispo, $reservation, $notificationService, $notifService);
                }
            }
            $this->addFlash('success', 'Votre demande a été envoyée avec succès ! Vous pouvez suivre l\'avancement de votre réservation sur cette page');
            return $this->redirectToRoute('app_list_postuler', ['id'=> $reservation->getId()], Response::HTTP_SEE_OTHER);
        }
        $hote = $security->getUser();
        $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());
        $counts = [];
        foreach ($reservations as $reservation) {
            $counts[$reservation->getId()] = count($reservation->getPostulers());
        }
        $currentDate = new \DateTime();
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'logements' => $logements,
            'form' => $form,
            'cible' => 'addReservation',
            'counts' => $counts,
            'now' => $currentDate->format('Y-m-d'),
        ]);
    }
    //button pour envoyer aux autres prestataires
    #[Route('/hote/{id}/envoyerAu', name: 'app_reservation_envoyerAutre', methods: ['GET', 'POST'])]
    public function envoyerAutre(EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService, Reservation $reservation): Response
    {
        try {
            $dispos = $this->getAvailablePrestataires($reservation, $entityManager);
            foreach($dispos as $dispo){
                $this->notifyPrestataire($dispo, $reservation, $notificationService, $notifService);
            }
            $this->addFlash('success', 'Votre demande a été envoyée avec succès ! Vous pouvez suivre l\'avancement de votre réservation sur cette page');
        }
        catch (\Exception $e) {
            // Handle other unexpected errors
            $this->addFlash('error', 'Une erreur inattendue est survenue.');
            // Again, logging can be useful here
            // $this->logger->error('Unexpected error during sending to other service providers: ' . $e->getMessage());
        }
    
        return $this->redirectToRoute('app_list_postuler', ['id'=> $reservation->getId()], Response::HTTP_SEE_OTHER);
    }

    
}
