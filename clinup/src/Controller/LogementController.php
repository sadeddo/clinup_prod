<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\User;
use App\Entity\Logement;
use Stripe\StripeClient;
use App\Form\LogementType;
use App\Entity\Subscription;
use Stripe\Checkout\Session;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SubscriptionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/logement')]
class LogementController extends AbstractController
{
    private $stripeSecretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->stripeSecretKey = $params->get('stripe_secret_key');
    }
    #[Route('/', name: 'app_logement_index', methods: ['GET'])]
    public function index(LogementRepository $logementRepository, Security $security): Response
    {
        return $this->render('logement/index.html.twig', [
            'logements' => $logementRepository->findBy(['hote' => $security->getUser()]),
        ]);
    }

    #[Route('/new', name: 'app_logement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Security $security,LogementRepository $logementRepository, SubscriptionRepository $subscriptionRepository): Response
    {
        $user = $security->getUser();
        // Count user's current properties using the new repository method
        $currentLogementCount = $logementRepository->countLogementsByHote($user);
        // Check if user has an active subscription
        $activeSubscription = $subscriptionRepository->findActiveSubscriptionByUser($user);
        // Check if user has 1 logement and no active subscription
        if ($currentLogementCount == 1 && !$activeSubscription) {
            return $this->redirectToRoute('subscription_needed');
        }
        if ($currentLogementCount == 2 && $activeSubscription && $activeSubscription->getType() === 'log2') {
            return $this->redirectToRoute('subscription_upgrade',['id' => $activeSubscription->getId()]);
        }
        if ($currentLogementCount >= 2 && !$activeSubscription) {
            return $this->redirectToRoute('subscription_reactif');
        }
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logement->setHote($security->getUser());
            $file = $form->get('img')->getData();
            if ($file) {
                // Générez un nom de fichier unique
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // Déplacez le fichier dans le répertoire où sont stockées les brochures
                try {
                    $file->move(
                        $this->getParameter('upload_directory_logement'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // ... gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }
        
                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $logement->setImg($fileName);
            }
            $entityManager->persist($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Votre logement est ajouté avec succès!
            Facilitez vos réservations en spécifiant toutes les tâches pour ce logement');
            return $this->redirectToRoute('app_task_index', ['id' => $logement->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/new.html.twig', [
            'logement' => $logement,
            'form' => $form,
        ]);
    }
    #[Route('/subscription-needed', name: 'subscription_needed', methods: ['GET'])]
    public function subscriptionNeeded(): Response
    {
        return $this->render('subscription/subscription_needed.html.twig');
    }
    //paiement abonnement
    #[Route('/checkout-session', name: 'checkout-session_abonnemet', methods: ['POST','GET'])]
    public function CheckoutSession(Request $request, EntityManagerInterface $entityManager,Security $security): JsonResponse
    {
        $user = $security->getUser();
        // Configurez votre clé secrète Stripe
        $stripe = new \Stripe\StripeClient($this->stripeSecretKey);
        //////produit 2 logement 
        try {
            $session = $stripe->checkout->sessions->create([
                'line_items' => [
                  [
                    'price' => 'price_1PO1nXGrwysY3nEfu9XnA8zU',
                    // For metered billing, do not pass quantity
                    'quantity' => 1,
                  ],
                ],
                'ui_mode' => 'embedded',
                'mode' => 'subscription',
                'customer_email' => $user->getEmail(),
                'success_url' => 'https://clinup.fr/logement/success-log?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://clinup.fr/logement/cancel-log2',
              ]);
            return new JsonResponse(['id' => $session->id]); // Renvoyer l'ID de la session au client
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    #[Route('/success-log', name: 'payment_successes_log')]
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
                throw new \Exception('Utilisateur non trouvé avec l\'email ' . $session->customer_email);
            }

            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setSubscriptionId($subscriptionId);
            $subscription->setSubscriptionItemId($subscriptionItemId);
            $subscription->setDtStart(new \DateTime('@' . $stripeSubscription->created));
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setType('log2');

            $entityManager->persist($subscription);
            $entityManager->flush();

            $this->addFlash('success', 'Votre abonnement a été activé avec succès. Vous pouvez ajouter votre deuxième logement dès maintenant.');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la gestion de votre abonnement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_logement_new');
    }
    #[Route('/cancel-log2', name: 'cancel-log2')]
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
    #[Route('/{id}/edit', name: 'app_logement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifiez si une nouvelle image a été soumise
            $file = $form->get('img')->getData();
            if ($file) {
                // Supprimer l'ancienne image si elle existe
                $oldFileName = $logement->getImg();
            if ($oldFileName && file_exists($this->getParameter('upload_directory_logement') . '/' . $oldFileName)) {
                unlink($this->getParameter('upload_directory_logement') . '/' . $oldFileName);
            }

                // Générez un nom de fichier unique pour la nouvelle image
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // Déplacez le fichier dans le répertoire où sont stockées les images
                try {
                    $file->move(
                        $this->getParameter('upload_directory_logement'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }

                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $logement->setImg($fileName);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Votre logement a été modifié avec succès !');
            return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/edit.html.twig', [
            'logement' => $logement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_logement_delete', methods: ['POST','GET'])]
    public function delete(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        // Supprimer d'abord toutes les tâches et images associées au logement
            foreach ($logement->getTasks() as $task) {
                // Supprimer toutes les images associées à la tâche
                foreach ($task->getImgTasks() as $imgTask) {
                    $entityManager->remove($imgTask);
                }

                // Ensuite, supprimer la tâche elle-même
                $entityManager->remove($task);
            }

            $entityManager->remove($logement);
            $entityManager->flush();
        return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
    }
}
