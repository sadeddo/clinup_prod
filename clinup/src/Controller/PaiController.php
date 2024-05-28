<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\User;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use App\Entity\Reservation;
use App\Service\EmailSender;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use App\Service\PdfGenerator;
use Symfony\Component\Uid\Uuid;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaiController extends AbstractController
{
    private $stripeSecretKey;
    private $logger;

    public function __construct(ParameterBagInterface $params,LoggerInterface $logger)
    {
        $this->stripeSecretKey = $params->get('stripe_secret_key');
        $this->logger = $logger;
    }

    #[Route('/{idPresta}/{idDemande}/checkout', name: 'checkout')]
    public function checkout(int $idPresta, int $idDemande, EntityManagerInterface $entityManager): Response
    {
        // Ici, vous récupérez vos données de réservation et de prestataire de la base de données
        // Assurez-vous que les entités et leurs propriétés sont correctement configurées
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idDemande]);
        $prestataire = $entityManager->getRepository(User::class)->findOneBy(['id' => $idPresta]);

        $cotTotal = $prestataire->getPrix(); ;

        if (!$reservation || !$prestataire) {
            $this->addFlash('error', 'Réservation ou prestataire introuvable.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('paiement/index.html.twig', [
            'reservation' => $reservation,
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

        $cotTotal = $prestataire->getPrix();

        if (!$reservation || !$prestataire) {
            $this->addFlash('error', 'Réservation ou prestataire introuvable.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('paiement/invit.html.twig', [
            'reservation' => $reservation,
            'prestataire' => $prestataire,
            'cotTotal' => $cotTotal
        ]);
    }

    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['cotTotal']; 
        $prestataire = $data['prestataire'];

        $uuid = Uuid::v4()->toRfc4122(); 
        Stripe::setApiKey($this->stripeSecretKey);

        try {
            $intent = PaymentIntent::create([
                'amount' => $amount * 100,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
                'payment_intent_data' => [
                    'transfer_group' => $uuid,
                ],        
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
            $tarif = 40;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 45;
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
            $tarif = 40;
        } elseif ($average > 4.5 && $numberOfMissions > 70) {
            $tier = 'Or';
            $tarif = 45;
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
    #[Route('/create-checkout-session-invit', name: 'create-checkout-session-invit', methods: ['POST','GET'])]
    public function tesst(){
        \Stripe\Stripe::setApiKey($this->stripeSecretKey); // Remplacez par votre clé API secrète

try {
    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Paiement pour service',
                ],
                'unit_amount' => 100, // 200,00 EUR
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://clinup.fr?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://clinup.fr',
        'payment_intent_data' => [
            'application_fee_amount' => 10, // 15,00 EUR de frais de plateforme
            'transfer_data' => [
                'destination' => 'acct_1PBmcfHKYhHd54cE' // ID du compte connecté du prestataire
            ],
        ],
    ]);
    echo 'Checkout Session ID: ' . $session->id;
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo 'Erreur lors de la création de la session : ' . $e->getMessage();
}
    }
//paiement invit 
    #[Route('/create-checkout-session/{id}', name: 'create-checkout-session', methods: ['POST','GET'])]
    public function createCheckoutSession(Request $request,Reservation $reservation): JsonResponse
    {

        // Configurez votre clé secrète Stripe
        Stripe::setApiKey($this->stripeSecretKey);

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => 'Service'],
                        'unit_amount' => $reservation->getPrix() * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => 'https://clinup.fr/success?session_id={CHECKOUT_SESSION_ID}&id_reservation='. urlencode($reservation->getId()),
                'cancel_url' => 'https://clinup.fr/cancel?id_reservation='. urlencode($reservation->getId()),
                'payment_intent_data' => [
                    'transfer_data' => [
                        'destination' => $reservation->getPrestataire()->getIdStripe(),  // Transférer au compte connecté
                        'amount' => $reservation->getPrix() * 100 - 1000,
                    ],
                ],
            ]);

            return new JsonResponse(['id' => $session->id]); // Renvoyer l'ID de la session au client
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    //success paiement invit
    #[Route('/success', name: 'payment_successes')]
    public function paymentSuccesses(Request $request, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService,PdfGenerator $pdfGenerator): Response
    {
        $sessionId = $request->query->get('session_id');
        $idReservation = $request->query->get('id_reservation');

        if (!$sessionId) {
            $this->addFlash(
                'error',  // Type de message, peut être 'error', 'success', etc.
                'Le processus de paiement a échoué et aucun paiement n\'a été effectué. Veuillez réessayer ou contacter notre support si le problème persiste.'
            );
            // Rediriger vers une autre route
            return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);
        }

        $stripe = new StripeClient($this->stripeSecretKey);
        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            // Trouver la réservation correspondante
            $reservation = $entityManager->getRepository(Reservation::class)->find($idReservation);
            if (!$reservation) {
                throw new \Exception("Reservation not found.");
            }

            // Mettre à jour le statut de la réservation
            $reservation->setIdIntent($sessionId);
            $reservation->setStatut('payer');

            $tasks = $reservation->getLogement()->getTasks();
            foreach ($tasks as $task) {
                foreach ($task->getImgTasks() as $imgTask) {
                    $entityManager->remove($imgTask);
                }
            }
            $entityManager->flush();
            //reçu
            $pdfGenerator->createReceipt($reservation->getId(),$entityManager);
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
            $this->addFlash(
                'success',
                'Votre prestation a été validée avec succès ! Votre prestataire recevra bientôt le paiement. Nous vous remercions de votre confiance.'
            );
            
            return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Un problème est survenu lors de la transaction. Veuillez nous excuser pour le désagrément. Nous vous invitons à réessayer ultérieurement ou à contacter notre support pour plus d\'assistance.'
            );
            return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);
        }

    }
    //canceled paiement invit
    #[Route('/cancel', name: 'payment_canceled')]
    public function paymentCanceled(Request $request, EntityManagerInterface $entityManager): Response
    {
        $idReservation = $request->query->get('id_reservation');
        // Ajouter un message flash
        $this->addFlash(
            'error',  // Type de message, peut être 'error', 'success', etc.
            'Le paiement a été annulé. Veuillez réessayer.'  // Message à afficher
        );

        // Rediriger vers une autre route
        return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);

    }
    //paiement non-invit 
    #[Route('/checkout-session/{reservationId}/{prestataireId}', name: 'checkout-session', methods: ['POST','GET'])]
    public function CheckoutSession($reservationId,$prestataireId,Request $request, EntityManagerInterface $entityManager): JsonResponse
    {

        // Configurez votre clé secrète Stripe
        $stripe = new \Stripe\StripeClient($this->stripeSecretKey);

        try {
            $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
            $prestataire = $entityManager->getRepository(User::class)->find($prestataireId);
            $uuid = Uuid::v4()->toRfc4122(); 
            $session = $stripe->checkout->sessions->create([
                'line_items' => [
                  [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => 'Service'],
                        'unit_amount' => $prestataire->getPrix() * 100,
                    ],
                    'quantity' => 1,
                  ],
                ],
                'payment_intent_data' => [
                  'transfer_group' => $uuid,
                ],
                'mode' => 'payment',
                'success_url' => 'http://localhost:8000/success-nInv?session_id={CHECKOUT_SESSION_ID}&id_reservation='. urlencode($reservation->getId()).'&grp='.$uuid.'&prestataire='.$prestataireId,
                'cancel_url' => 'http://localhost:8000/cancel?id_reservation='. urlencode($reservation->getId()),
              ]);
            return new JsonResponse(['id' => $session->id]); // Renvoyer l'ID de la session au client
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    //success paiement invit
    #[Route('/success-nInv', name: 'payment_successes_nInv')]
    public function paymentSuccessesNonIn(Request $request, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService,ReservationRepository $reservationRepository,CommentPrestaRepository $commentPrestaRepository): Response
    {
        $sessionId = $request->query->get('session_id');
        $idReservation = $request->query->get('id_reservation');
        $grp = $request->query->get('grp');
        $idPrestataire = $request->query->get('prestataire');

        if (!$sessionId) {
            $this->addFlash(
                'error',  // Type de message, peut être 'error', 'success', etc.
                'Le processus de paiement a échoué et aucun paiement n\'a été effectué. Veuillez réessayer ou contacter notre support si le problème persiste.'
            );
            // Rediriger vers une autre route
            return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);
        }

        $stripe = new StripeClient($this->stripeSecretKey);
        Stripe::setApiKey($this->stripeSecretKey);
        
        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            $idPaiement = $session->payment_intent;
            $paymentIntent = \Stripe\PaymentIntent::retrieve($idPaiement);
            // Trouver la réservation correspondante
            $reservation = $entityManager->getRepository(Reservation::class)->find($idReservation);
            $prestataire = $entityManager->getRepository(User::class)->find($idPrestataire);
            if (!$reservation) {
                throw new \Exception("Reservation not found.");
            }

            // Mettre à jour le statut de la réservation
            $reservation->setIdIntent($paymentIntent->latest_charge);
            $reservation->setStatut('confirmer');
            $reservation->setPrestataire($prestataire);
            $reservation->setPrix($prestataire->getPrix());

            $entityManager->flush();

            $notificationService->sendEmail(
                $reservation->getPrestataire()->getEmail(),
                'Prestation confirmée',
                'email/confirmPresta.html.twig',
                ['user' => $reservation->getPrestataire()]
            );

            $notifService->createNotification(
                $reservation->getPrestataire(),
                'Prestation confirmée',
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
            $this->addFlash(
                'success',
                'Paiement et réservation confirmés.'
            );
            return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Un problème est survenu lors de la transaction. Veuillez nous excuser pour le désagrément. Nous vous invitons à réessayer ultérieurement ou à contacter notre support pour plus d\'assistance.'.$e
            );
            return $this->redirectToRoute('app_list_postuler', ['id'=> $idReservation]);
        }

    }
}
