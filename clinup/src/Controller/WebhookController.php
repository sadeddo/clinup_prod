<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\User;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\StripeClient;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function index(LoggerInterface $logger, EntityManagerInterface $entityManager): Response
    {
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));
        $endpointSecret = $this->getParameter('stripe_webhook_secret');

        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            $logger->error('Stripe webhook invalid payload');
            return new Response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            $logger->error('Stripe webhook invalid signature');
            return new Response('Invalid signature', 403);
        }

        $stripe = new StripeClient($this->getParameter('stripe_secret_key'));

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $subscriptionId = $session->subscription;
                $customerEmail = $session->customer_details->email ?? null;

                if (!$subscriptionId || !$customerEmail) {
                    $logger->error('Missing subscriptionId or email');
                    break;
                }

                $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $customerEmail]);
                if (!$user) {
                    $logger->error("User not found for email {$customerEmail}");
                    break;
                }

                // Désactiver l'ancien abonnement actif (via status)
                $oldSub = $entityManager->getRepository(Subscription::class)->findActiveSubscriptionByUser($user);
                if ($oldSub) {
                    $oldSub->setStatus('canceled');
                    $entityManager->persist($oldSub);
                    try {
                        $stripe->subscriptions->cancel($oldSub->getSubscriptionId(), []);
                    } catch (\Exception $e) {
                        $logger->warning("Stripe cancel failed: " . $e->getMessage());
                    }
                }

                // Créer le nouvel abonnement
                $stripeSub = $stripe->subscriptions->retrieve($subscriptionId, []);
                $item = $stripeSub->items->data[0] ?? null;

                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setSubscriptionId($subscriptionId);
                $subscription->setSubscriptionItemId($item?->id);
                $subscription->setDtStart(new DateTimeImmutable('@' . $stripeSub->current_period_start));
                $subscription->setDtEnd(new DateTimeImmutable('@' . $stripeSub->current_period_end));
                $subscription->setStatus($stripeSub->status); // "active"
                $subscription->setType($item?->price?->id ?? 'unknown');

                $entityManager->persist($subscription);
                $entityManager->flush();

                $logger->info("New subscription created for user {$user->getEmail()}");
                break;

            case 'customer.subscription.updated':
                $subObj = $event->data->object;
                $subscription = $entityManager->getRepository(Subscription::class)
                    ->findOneBy(['subscriptionId' => $subObj->id]);

                if ($subscription) {
                    $subscription->setStatus($subObj->status);
                    $subscription->setDtStart(new DateTimeImmutable('@' . $subObj->current_period_start));
                    $subscription->setDtEnd(new DateTimeImmutable('@' . $subObj->current_period_end));

                    $entityManager->flush();
                    $logger->info("Subscription {$subObj->id} updated");
                }
                break;

            case 'invoice.payment_failed':
                $subId = $event->data->object->subscription;
                $subscription = $entityManager->getRepository(Subscription::class)
                    ->findOneBy(['subscriptionId' => $subId]);
                if ($subscription) {
                    $subscription->setStatus('payment_failed');
                    $entityManager->flush();
                    $logger->warning("Payment failed for subscription {$subId}");
                }
                break;

            case 'customer.subscription.deleted':
                $subObj = $event->data->object;
                $subscription = $entityManager->getRepository(Subscription::class)
                    ->findOneBy(['subscriptionId' => $subObj->id]);
                if ($subscription) {
                    $subscription->setStatus('canceled');
                    $subscription->setDtEnd(new DateTimeImmutable());
                    $entityManager->flush();
                    $logger->info("Subscription {$subObj->id} canceled");
                }
                break;

            default:
                $logger->info("Unhandled Stripe event: " . $event->type);
        }

        return new Response('success', 200);
    }
}
