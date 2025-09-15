<?php

namespace App\Controller;

use DateTime;
use Exception;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Entity\User;
use Stripe\StripeClient;
use App\Entity\Subscription;
use Stripe\Checkout\Session;
use UnexpectedValueException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SubscriptionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SubscriptionController extends AbstractController
{
    private $stripeSecretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->stripeSecretKey = $params->get('stripe_secret_key');
    }
    #[Route('/sub_upgrade/{id}', name: 'sub_upgrade')]
    public function upgrade(Subscription $subscription, Security $security, EntityManagerInterface $entityManager): Response
    {
        $stripe = new StripeClient($this->stripeSecretKey);

        try {
            $upg = $stripe->subscriptions->update(
                $subscription->getSubscriptionId(),
                [
                    'proration_behavior' => 'always_invoice',
                    'items' => [
                        [
                            'id' => $subscription->getSubscriptionItemId(),
                            'deleted' => true,
                        ],
                        ['price' => 'price_1PO1oiGrwysY3nEfk1WG22Bt'],
                    ],
                ]
            );
            // Récupérer le nouvel Subscription Item ID
            $newSubscriptionItemId = null;
            foreach ($upg->items->data as $item) {
                if ($item->price->id === 'price_1PO1oiGrwysY3nEfk1WG22Bt') {
                    $newSubscriptionItemId = $item->id;
                    break;
                }
            }

            if ($newSubscriptionItemId) {
                // Mettre à jour l'abonnement en base de données
                $subscription->setType('log3');
                $subscription->setSubscriptionItemId($newSubscriptionItemId);
                $entityManager->flush();

                // Ajouter un message de succès
                $this->addFlash('success', 'Votre abonnement a été mis à jour avec succès.');
            } else {
                // Ajouter un message d'erreur si le nouvel Subscription Item ID n'a pas été trouvé
                $this->addFlash('error', 'La mise à jour de l\'abonnement a échoué : le nouvel Subscription Item ID n\'a pas été trouvé.');
            }
        } catch (\Exception $e) {
            // Si une exception se produit, ajoutez un message d'erreur
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de votre abonnement : ' . $e->getMessage());
        }

        // Redirigez l'utilisateur vers une page appropriée (par exemple, le tableau de bord ou la page des abonnements)
        return $this->redirectToRoute('app_logement_new'); // Assurez-vous que 'dashboard' ne redirige pas vers 'subscription_upgrade'
    }
    #[Route('/subscription-upgrade', name: 'subscription_upgrade', methods: ['GET'])]
    public function subscriptionNeeded(): Response
    {
        return $this->render('subscription/subscription_upgrade.html.twig');
    }
    #[Route('/testdes', name: 'subs', methods: ['GET','POST'])]
    public function subsc(): Response
    {
        return $this->render('subscription/cancel.html.twig');
    }
    #[Route('/subscription_reactif', name: 'subscription_reactif', methods: ['GET','POST'])]
    public function react(){
        return $this->render('subscription/reactif.html.twig');
    }
    #[Route('/sub/checkout-session', name: 'checkout_session_reactif')]
    public function checkoutReactif(Request $request, EntityManagerInterface $entityManager,Security $security){
        $user = $security->getUser();
        // Configurez votre clé secrète Stripe
        $stripe = new \Stripe\StripeClient($this->stripeSecretKey);

        try {
            $session = $stripe->checkout->sessions->create([
                'line_items' => [
                  [
                    'price' => 'price_1PO1oiGrwysY3nEfk1WG22Bt',
                    // For metered billing, do not pass quantity
                    'quantity' => 1,
                  ],
                ],
                'mode' => 'subscription',
                'customer_email' => $user->getEmail(),
                'success_url' => 'https://clinup.fr/sub/success-log?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://clinup.fr/sub/cancel-log2',
              ]);
            return new JsonResponse(['id' => $session->id]); // Renvoyer l'ID de la session au client
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    #[Route('/sub/success-log', name: 'payment_successes_reactif')]
    public function paymentSuccessesLog(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionId = $request->query->get('session_id');

        if (!$sessionId) {
            $this->addFlash('error', 'Session ID manquant.');
            return $this->redirectToRoute('app_logement_new');
        }

        Stripe::setApiKey($this->stripeSecretKey);

        try {
            $session = Session::retrieve($sessionId);
            $subscriptionId = $session->subscription;

            if (!$subscriptionId) {
                throw new \Exception('Subscription ID manquant dans la session.');
            }

            $stripeSubscription = StripeSubscription::retrieve($subscriptionId);
            $subscriptionItemId = $stripeSubscription->items->data[0]->id;

            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $session->customer_email]);

            if (!$user) {
                throw new Exception('Utilisateur non trouvé avec l\'email ' . $session->customer_email);
            }

            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setSubscriptionId($subscriptionId);
            $subscription->setSubscriptionItemId($subscriptionItemId);
            $subscription->setDtStart(new DateTime('@' . $stripeSubscription->created));
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setType('log3');

            $entityManager->persist($subscription);
            $entityManager->flush();

            $this->addFlash('success', 'Votre abonnement a été activé avec succès. Vous pouvez ajouter un autre logement dès maintenant.');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la gestion de votre abonnement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_logement_new');
    }
    #[Route('/sub/cancel-log2', name: 'cancel_reactif')]
    public function paymentCanceled(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Ajouter un message flash
        $this->addFlash(
            'error',  // Type de message, peut être 'error', 'success', etc.
            'Une erreur est survenue. Veuillez réessayer plus tard'  // Message à afficher
        );
        // Rediriger vers une autre route
        return $this->redirectToRoute('app_logement_new');

    }
    #[Route('/abonnement', name: 'app_abonnement', methods: ['GET', 'POST'])]
    public function index(SubscriptionRepository $subscriptionRepository,Security $security){
        $user = $security->getUser();
        $activeSubscription = $subscriptionRepository->findActiveSubscriptionByUser($user);
        return $this->render('subscription/index.html.twig', [
            'subscription' => $activeSubscription,
        ]);
    }
    #[Route('/subscription_cancel/{id}', name: 'subscription_cancel', methods: ['POST','GET'])]
    public function cancel(Subscription $subscription, Security $security, EntityManagerInterface $entityManager): Response
    {
        $stripe = new StripeClient($this->stripeSecretKey);

        try {
            $stripe->subscriptions->update(
                $subscription->getSubscriptionId(),
                ['cancel_at_period_end' => true]
              );

            $subscription->setStatus('canceled');
            $subscription->setDtEnd(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Votre abonnement a été annulé avec succès.');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'annulation de votre abonnement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_abonnement');
    }

}
