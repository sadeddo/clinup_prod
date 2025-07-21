<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Dispo;
use App\Entity\Reservation;
use App\Service\EmailSender;
use App\Service\IcalService;
use App\Form\ReservationType;
use App\Form\SearchPrestaType;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Repository\IcalresRepository;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


final class PrestataireController extends AbstractController
{

#[Route('/prestataires', name: 'app_tous_prestataires', methods: ['GET', 'POST'])]
public function listePrestataires(
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $em
): Response {
    $form = $this->createForm(SearchPrestaType::class);
    $form->handleRequest($request);

    $prestataires = [];

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $adresseClient = $data['adresse'];
        $dateRecherche = $data['date'];

        $coordsClient = $this->geocodeAdresse($adresseClient);

        if ($coordsClient) {
            $dispos = $em->getRepository(Dispo::class)->findBy(['date' => $dateRecherche]);

            foreach ($dispos as $dispo) {
                $presta = $dispo->getPrestataire();

                if (!$presta instanceof User) {
                    $presta = $em->getRepository(User::class)->find($presta);
                }

                // Ne garder que les prestataires vérifiés
                if (!$presta || !$presta->isIsVerified()) continue;

                $coordsPresta = $this->geocodeAdresse($presta->getAdresse());
                if (!$coordsPresta) continue;

                $distance = $this->distanceKm(
                    $coordsClient['lat'], $coordsClient['lon'],
                    $coordsPresta['lat'], $coordsPresta['lon']
                );

                if ($distance <= 20) {
                    $prestataires[] = $presta;
                }
            }
        }
    } else {
        // Si aucun filtre, afficher tous les prestataires vérifiés
        $prestataires = $userRepository->findVerifiedPrestataires();
    }

    return $this->render('prestataire/index.html.twig', [
        'form' => $form->createView(),
        'prestataires' => $prestataires,
    ]);
}

private function geocodeAdresse(string $adresse): ?array
{
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($adresse);
    $opts = [
        "http" => [
            "header" => "User-Agent: Clinup/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (!empty($data[0])) {
        return [
            'lat' => (float) $data[0]['lat'],
            'lon' => (float) $data[0]['lon']
        ];
    }

    return null;
}

private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

#[Route('/prestataire/{id}/consulter', name: 'app_tous_prestataires_consulter', methods: ['GET', 'POST'])]
public function prestataire(
    int $id,
    UserRepository $userRepository,
    CommentPrestaRepository $commentPrestaRepository
): Response {
    $prestataire = $userRepository->findOneBy(['id'=> $id]);
    $average = $commentPrestaRepository->getAverageNoteForPrestataire($prestataire->getId());

    // Création des événements à afficher dans le calendrier
    $disponibilites = [];
    foreach ($prestataire->getDispos() as $dispo) {
        $disponibilites[] = [
            'start' => $dispo->getDate()->format('Y-m-d') . 'T' . $dispo->getDtStart(),
            'end'   => $dispo->getDate()->format('Y-m-d') . 'T' . $dispo->getDtEnd(),
            'title' => 'Disponible'
        ];
    }

    return $this->render('prestataire/consulter.html.twig', [
        'presta' => $prestataire,
        'average' => $average,
        'disposJson' => json_encode($disponibilites),
        'cible' => '',
    ]);
}

    #[Route('/presta/{id}/ajouter', name: 'app_reservation_ajouter_direct', methods: ['GET', 'POST'])]
public function ajouter(
    int $id,
    Request $request,
    EntityManagerInterface $entityManager,
    Security $security,
    EmailSender $notificationService,
    NotificationService $notifService,
    ReservationRepository $reservationRepository,
    IcalService $icalService,
    IcalresRepository $icalresRepository,
    LogementRepository $logementRepository,
    UserRepository $userRepository,
    CommentPrestaRepository $commentPrestaRepository // Ajout du repo des avis
): Response {
    // Vérification que le prestataire existe
    $prestataire = $userRepository->findOneBy(['id' => $id]);
    if (!$prestataire) {
        $this->addFlash('error', 'Le prestataire sélectionné n\'existe pas.');
        return $this->redirectToRoute('app_tous_prestataires');
    }

    // Récupération de la note moyenne du prestataire
    $average = $commentPrestaRepository->getAverageNoteForPrestataire($prestataire->getId()) ?? 0;

    // Récupération des logements de l'hôte connecté
    $hote = $security->getUser();
    $logements = $logementRepository->findBy(['hote' => $hote]);

    // Création du formulaire de réservation
    $reservation = new Reservation();
    $form = $this->createForm(ReservationType::class, $reservation, [
        'user' => $hote,
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $reservation->setStatut("en attente");
        $reservation->setIdIntent('direct');
        $reservation->setPrestataire($prestataire);
        $entityManager->persist($reservation);
        $entityManager->flush();
 // Envoi d'un email au prestataire
        $notificationService->sendEmail(
            $prestataire->getEmail(),
            
            'Nouvelle Réservation Disponible',
            'email/nvReservation.html.twig',
            [
                'user' => $prestataire, 
            ]
        );

        // Création d'une notification pour le prestataire
        $notifContent = 'Une nouvelle opportunité de réservation correspondant à vos disponibilités et votre zone géographique vient d\'apparaître sur notre plateforme.';
        $notifService->createNotification(
            $prestataire,
            $notifContent,
            '/reservation/prestataire/' . $reservation->getId() . '/consulter'
        );

        $this->addFlash('success', 'Votre demande a été envoyée avec succès ! Vous pouvez suivre l\'avancement de votre réservation sur cette page');
        return $this->redirectToRoute('app_list_postuler', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
    }

    // Récupération des réservations pour affichage
    $reservations = $reservationRepository->findReservationsByPrestataire($hote->getId());
    $counts = [];
    foreach ($reservations as $reservation) {
        $counts[$reservation->getId()] = count($reservation->getPostulers());
    }

    $currentDate = new \DateTime();

    return $this->render('prestataire/consulter.html.twig', [
        'reservations' => $reservations,
        'logements' => $logements,
        'form' => $form,
        'cible' => 'addReservation',
        'counts' => $counts,
        'average' => $average, // Ajout de la virgule manquante
        'now' => $currentDate->format('Y-m-d'),
        'presta' => $prestataire
    ]);
}


}
