<?php

namespace App\Controller;

use DateTime;
use Stripe\Stripe;
use App\Entity\User;
use Stripe\Transfer;
use App\Entity\Dispo;
use App\Entity\Notif;
use DateTimeImmutable;
use App\Entity\Postuler;
use Stripe\StripeClient;
use App\Form\PostulerType;
use App\Entity\Reservation;
use App\Service\EmailSender;
use App\Form\ReservationType;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Repository\PostulerRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    public function index(Security $security, ReservationRepository $reservationRepository): Response
    {
        $hote = $security->getUser();
        $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());

        $counts = [];
        foreach ($reservations as $reservation) {
            $counts[$reservation->getId()] = count($reservation->getPostulers());
        }
        
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'counts' => $counts,
            'cible' => ''
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
    #[Route('/hote/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager,Security $security, EmailSender $notificationService,NotificationService $notifService, ReservationRepository $reservationRepository): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'user' => $security->getUser(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->createReservation($reservation, $entityManager, $security);
            $dispos = $this->getAvailablePrestataires($reservation, $entityManager);
        foreach($dispos as $dispo){
            $this->notifyPrestataire($dispo, $reservation, $notificationService, $notifService);
        }

         return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
        $hote = $security->getUser();
        $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());
        $counts = [];
        foreach ($reservations as $reservation) {
            $counts[$reservation->getId()] = count($reservation->getPostulers());
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'form' => $form,
            'cible' => 'addReservation',
            'counts' => $counts,
        ]);
    }

    #[Route('/prestataire/{id}/consulter', name: 'app_reservation_show', methods: ['GET','POST'])]
    public function show(Reservation $reservation,EntityManagerInterface $entityManager,Security $security): Response
    {
        //verifier si l'utilisateur à deja postuler
        $postulation = $entityManager->getRepository(Postuler::class)
                    ->findOneBy(['reservation' => $reservation, 'prestataire' => $security->getUser()]);
                 
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'cible' => '',
            'hasApplied' => $postulation !== null
        ]);
    }
    //comment postuler
    #[Route('/prestataire/{id}/postuler', name: 'app_reservation_postuler', methods: ['GET','POST'])]
    public function postuler($id,Security $security,Request $request,Reservation $reservation, EntityManagerInterface $entityManager,EmailSender $notificationService,NotificationService $notifService): Response
    {
        $comment = new Postuler();
        $form = $this->createForm(PostulerType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPrestataire($security->getUser());
            $comment->setReservation($reservation);
            $entityManager->persist($comment);
            $entityManager->flush();
            $notificationService->sendEmail(
                $reservation->getLogement()->getHote()->getEmail(),
                'Nouvelle Candidature pour Votre Réservation',
                'email/prestaPostule.html.twig',
                [
                    'user' => $security->getUser(), // Objet ou tableau contenant les informations de l'utilisateur
                    
                ]
            );
            $notifService->createNotification(
                $reservation->getLogement()->getHote()->getEmail(),
                'Nouvelle Candidature pour Votre Réservation',
                '/reservation/hote/'.$comment->getId().'/postulers'
    
            );
            return $this->redirectToRoute('app_reservation_show', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        //verifier si l'utilisateur à deja postuler
        $postulation = $entityManager->getRepository(Postuler::class)
                    ->findOneBy(['reservation' => $reservation, 'prestataire' => $security->getUser()]);
                    
        return $this->render('reservation/show.html.twig', [
            'comment' => $comment,
            'form' => $form,
            'reservation' => $reservation,
            'cible' => 'postuler',
            'hasApplied' => $postulation !== null
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
        $geocodeFrom = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrFrom.'&sensor=false&key='.$apiKey);
        $outputFrom = json_decode($geocodeFrom);
        if(!empty($outputFrom->error_message)){
            return $outputFrom->error_message;
        }
        
        // Geocoding API request with end address
        $geocodeTo = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrTo.'&sensor=false&key='.$apiKey);
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
    public function consulter(Reservation $reservation,PostulerRepository $PostulerRepository)
    {
        
        return $this->render('reservation/postuler.html.twig', [
            'reservation' => $reservation,
            
        ]);
    }
    
    //pour la liste des reservation  pour prestataire:
    #[Route('/prestataire/consulter', name: 'app_reservation_prestataire', methods: ['GET'])]
    public function indexP(Security $security, ReservationRepository $reservationRepository): Response
    {
        $hote = $security->getUser();
        $allDemandes = $reservationRepository->findReservationsByHote($hote->getId());
        $demandesParStatut = [];
        foreach ($allDemandes as $demande) {
            $statut = $demande->getStatut(); 
        if (!isset($demandesParStatut[$statut])) {
            $demandesParStatut[$statut] = [];
        }
        $demandesParStatut[$statut][] = $demande;
        }
        return $this->render('reservation/reservationP.html.twig', [
            'demande_services_par_statut' => $demandesParStatut,
        ]);
    }
    //la liste des personne postuler pour une reservation:
    #[Route('/hote/{id}/choisir', name: 'app_reservation_choisir', methods: ['GET'])]
    public function choisir($id,Security $security, PostulerRepository $postulerRepository): Response
    {
        $prestataire = $security->getUser();
        $comments = $postulerRepository->findBy(['reservation' => $id]);
        return $this->render('reservation/postuler.html.twig', [
            'prestataires' => $comments
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
        //calculer le prix
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
            $tarif = 45;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 60;
        }
        $prestataire->setPrix($tarif);
        $entityManager->persist($prestataire);
        $entityManager->flush();

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    //valider la reservation
    #[Route('/hote/{id}/valider', name: 'app_reservation_valider', methods: ['POST','GET'])]
    public function valider(Request $request, Reservation $reservation, EntityManagerInterface $entityManager, EmailSender $notificationService,NotificationService $notifService): Response
    {
        $idIntent = $reservation->getIdIntent();

        $stripe = new StripeClient($this->stripeSecretKey);
        $stripe->paymentIntents->capture($idIntent, []);

        $stripee = Stripe::setApiKey($this->stripeSecretKey);
        $montantEuro = $reservation->getPrix(); 
        $montantEuro = str_replace(',', '.', $montantEuro); // Remplacez la virgule par un point pour la conversion
        $montantNombre = floatval($montantEuro);

        // Soustrayez 3,09 du montant en euros
        $montantNombre = $montantNombre - 3.09;

        // Convertissez le nouveau montant en centimes
        $montantCentimes = $montantNombre * 100;
        Transfer::create([
            'amount' => '77', // Montant en centimes
            'currency' => 'eur',
            'destination' => $reservation->getPrestataire()->getIdStripe(), // ID du compte Stripe du prestataire
        ]);

        $reservation->setStatut('payer');
        $entityManager->persist($reservation);
        $tasks = $reservation->getLogement()->getTasks();
        foreach ($tasks as $task) {
            // Obtenir toutes les images liées à la tâche
            $imgTasks = $task->getImgTasks(); // Assurez-vous que cette méthode existe dans votre entité Task
    
            foreach ($imgTasks as $imgTask) {
                // Supprimer chaque imgTask
                $entityManager->remove($imgTask);
            }
        }
            $entityManager->flush();
            $notificationService->sendEmail(
                $reservation->getPrestataire()->getEmail(),
                'Prestation Validée',
                'email/validePrestation.html.twig',
                [
                    'user' => $reservation->getPrestataire(), // Objet ou tableau contenant les informations de l'utilisateur
                ]
            );
            $notifService->createNotification(
                $reservation->getPrestataire(),
                'Prestation Validée',
                '/reservation/prestataire/consulter'
    
            );
            
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    //annuler la réservation
    #[Route('/hote/{id}/annuler', name: 'app_reservation_annuler', methods: ['POST','GET'])]
    public function annuler(Request $request, Reservation $reservation, EntityManagerInterface $entityManager, EmailSender $emailService,NotificationService $notifService): Response
    {
            $idIntent = $reservation->getIdIntent();
            $stripe = new StripeClient($this->stripeSecretKey);
            $stripe->paymentIntents->cancel($idIntent, []);
            // modifier le statut
            $reservation->setStatut('Annuler');
            $entityManager->persist($reservation);
            $entityManager->flush();
            // envoyer un email au prestataire pour l'informer
            $emailService->sendEmail(
                $reservation->getPrestataire()->getEmail(),
                'Réservation Annulée',
                'email/AnnulPresta.html.twig',
                [
                    'user' => $reservation->getPrestataire(),
                ]
            );
            // envoyer une notif sur l'app
            $notifService->createNotification(
                $reservation->getPrestataire(),
                'Réservation Annulée',
                '/reservation/prestataire/consulter'
    
            );
            
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
    
}
