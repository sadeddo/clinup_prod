<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\User;
use Stripe\PaymentIntent;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaiController extends AbstractController
{
    private $stripeSecretKey;

    private $stripePublicKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->stripeSecretKey = $params->get('stripe_secret_key');
    }

    #[Route('/{idPresta}/{idDemande}/checkout', name: 'checkout')]
    public function checkout(int $idPresta, int $idDemande, EntityManagerInterface $entityManager): Response
    {
        // Ici, vous récupérez vos données de réservation et de prestataire de la base de données
        // Assurez-vous que les entités et leurs propriétés sont correctement configurées
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idDemande]);
        $prestataire = $entityManager->getRepository(User::class)->findOneBy(['id' => $idPresta]);

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

        if (!$reservation || !$prestataire) {
            $this->addFlash('error', 'Réservation ou prestataire introuvable.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('paiement/index.html.twig', [
            'reservation' => $reservation,
            'stripePublicKey' => $this->stripePublicKey,
            'prestataire' => $prestataire,
            'cotTotal' => $cotTotal
        ]);
    }
    //checkout invit
    #[Route('/{idPresta}/{idDemande}/checkoutI', name: 'checkoutI')]
    public function checkoutInvit(int $idPresta, int $idDemande, EntityManagerInterface $entityManager): Response
    {
        // Ici, vous récupérez vos données de réservation et de prestataire de la base de données
        // Assurez-vous que les entités et leurs propriétés sont correctement configurées
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idDemande]);
        $prestataire = $entityManager->getRepository(User::class)->findOneBy(['id' => $idPresta]);

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

        if (!$reservation || !$prestataire) {
            $this->addFlash('error', 'Réservation ou prestataire introuvable.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('paiement/invit.html.twig', [
            'reservation' => $reservation,
            'stripePublicKey' => $this->stripePublicKey,
            'prestataire' => $prestataire,
            'cotTotal' => $cotTotal
        ]);
    }

    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount']; 

        Stripe::setApiKey($this->stripeSecretKey);

        try {
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
            ]);

            return new JsonResponse(['clientSecret' => $intent->client_secret]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    #[Route('/payment-success', name: 'payment_success', methods: ['POST','GET'])]
public function paymentSuccess(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    // Vérifiez que vous avez reçu les données attendues
    if (!$data || !isset($data['paymentIntentId'], $data['reservationId'], $data['prestataireId'])) {
        // Retournez une erreur si les données nécessaires ne sont pas présentes
        return new JsonResponse(['error' => 'Données de paiement manquantes ou invalides'], Response::HTTP_BAD_REQUEST);
    }

    $paymentIntentId = $data['paymentIntentId'];
    $reservationId = $data['reservationId'];
    $prestataireId = $data['prestataireId'];
    $cotTotal = $data['cotTotal'];
    $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $reservationId ]);
    $prestataire = $entityManager->getRepository(User::class)->findOneBy(['id' => $prestataireId ]);
    $reservation->setStatut('confirmer');
    $reservation->setIdIntent($paymentIntentId);
    $reservation->setPrix($cotTotal);
    $reservation->setPrestataire($prestataire);
    $entityManager->persist($reservation);
    $entityManager->flush();
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
    return new JsonResponse(['status' => 'success', 'message' => 'Paiement et réservation confirmés.']);

}

    //#[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function confirm(Request $request)
    {
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
    }
}
