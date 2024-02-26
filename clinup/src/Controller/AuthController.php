<?php

namespace App\Controller;

use Exception;
use Stripe\Stripe;
use Stripe\Account;
use App\Entity\User;
use App\Form\RegisterType;
use App\Service\EmailSender;
use App\Service\NotificationService;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthController extends AbstractController
{
    
    //connexion
    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    //déconnexion
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    //Pour hasher le mdp dans la base de données
    public function __construct(UserPasswordHasherInterface $hasher,ParameterBagInterface $params)
    {
        $this->hasher = $hasher;
        $this->stripeSecretKey = $params->get('stripe_secret_key');
    }
    
    //inscription
    #[Route(path: '/inscription', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRole = $form->get('role')->getData();
            if (null === $selectedRole) {
                $this->addFlash('error', 'Veuillez sélectionner un rôle.');
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            $user->setRoles([$selectedRole]);
            $user->setPicture('default.png');
            $user->setPrix('35');
            $user->setPassword($this->hasher->hashPassword($user, $form->get("password")->getData()));
    
            try {
                Stripe::setApiKey($this->stripeSecretKey);
                $account = Account::create([
                    'type' => 'express',
                    'country' => 'FR',
                    'email' => $user->getEmail(),
                    'capabilities' => [
                        'transfers' => ['requested' => true],
                    ],
                    'business_type' => 'individual',
                ]);
                $user->setIdStripe($account->id);
                $user->setStatutStripe('0');
            } catch (ApiErrorException $e) {
                $this->addFlash('error', 'Un problème est survenu lors de la création du compte Stripe. Veuillez réessayer plus tard.');
                return $this->render('security/register.html.twig', ['form' => $form->createView()]);
            } catch (Exception $e) {
                $this->addFlash('error', 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.');
                return $this->render('security/register.html.twig', ['form' => $form->createView()]);
            }
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            //envois de mail
            if (in_array("ROLE_PRESTATAIRE", $user->getRoles())) {
                $notificationService->sendEmail(
                    $user->getEmail(),
                    'Bienvenue sur ClinUpe',
                    'email/inscriptionP.html.twig',
                    [
                        'user' => $user, // Objet ou tableau contenant les informations de l'utilisateur
                    ]
                );
                $notifService->createNotification(
                    $user,
                    'Pour commencer à recevoir des missions, n\'oubliez pas de compléter votre profil. Cela nous permettra de vérifier et de valider votre compte.',
                    ''
        
                );
            }
            elseif(in_array("ROLE_HOTE", $user->getRoles())){
                $notificationService->sendEmail(
                    $user->getEmail(),
                    'Bienvenue sur ClinUp',
                    'email/inscriptionH.html.twig',
                    [
                        'user' => $user, // Objet ou tableau contenant les informations de l'utilisateur
                    ]
                );
                $notifService->createNotification(
                    $user,
                    'Découvrez des prestataires près de chez vous et réservez en toute simplicité et sécurité grâce à ClinUp.',
                    ''
        
                );
            }
            if (in_array("ROLE_ADMIN", $user->getRoles())) {
                $notifService->createNotification(
                    $user,
                    'Nouveau Prestataire sur ClinUp',
                    ''
        
                );
            }
    
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}