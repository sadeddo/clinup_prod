<?php

namespace App\Controller;

use Exception;
use Stripe\Stripe;
use Stripe\Account;
use App\Entity\User;
use App\Form\RegisterType;
use App\Service\JWTService;
use App\Service\EmailSender;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use App\Repository\InvitRepository;
use Symfony\Component\Mime\Address;
use App\Service\NotificationService;
use Stripe\Exception\ApiErrorException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthController extends AbstractController
{
    //connexion
    #[Route(path: '/connexion', name: 'app_login')]
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
    public function register(Request $request, EntityManagerInterface $entityManager, EmailSender $notificationService, NotificationService $notifService, InvitRepository $invitRepository,JWTService $jwt): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //invit
            $codeInvitation = $form->get('code')->getData();
            if (!empty($codeInvitation)) {
                $invitation = $invitRepository->findOneBy(['code' => $codeInvitation]);
                if (!$invitation || $invitation->getEtat() !== 'en attente') {
                    $this->addFlash('error', 'Le code d\'invitation est invalide ou a déjà été utilisé.');
                    return $this->redirectToRoute('app_register');
                } else {
                    $invitation->setEtat('accepter');
                    $invitation->setPresta($user);
                    $entityManager->persist($invitation);
                    $entityManager->flush();
                }
            }
            $selectedRole = $form->get('role')->getData();
            if (null === $selectedRole) {
                $this->addFlash('error', 'Veuillez sélectionner un rôle.');
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            $user->setRoles([$selectedRole]);
            $user->setPalier('Bronze');
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

            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];

            // On crée le Payload
            $payload = [
                'user_id' => $user->getId()
            ];

            // On génère le token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
            //envois de mail
            if (in_array("ROLE_PRESTATAIRE", $user->getRoles())) {
                $notificationService->sendEmail(
                    $user->getEmail(),
                    'Bienvenue sur ClinUpe',
                    'email/inscriptionP.html.twig',
                    [
                        'user' => $user,
                        'token' => $token // Objet ou tableau contenant les informations de l'utilisateur
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
                        'user' => $user,
                        'token' => $token // Objet ou tableau contenant les informations de l'utilisateur
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
    
            return $this->redirectToRoute('app_new_res');
        }
    
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UserRepository $usersRepository, EntityManagerInterface $em): Response
    {
        //On vérifie si le token est valide, n'a pas expiré et n'a pas été modifié
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            // On récupère le payload
            $payload = $jwt->getPayload($token);

            // On récupère le user du token
            $user = $usersRepository->find($payload['user_id']);

            //On vérifie que l'utilisateur existe et n'a pas encore activé son compte
            if($user && !$user->IsVeryEmail()){
                $user->SetVeryEmail(true);
                $em->flush($user);
                $this->addFlash('success', 'Compte activé');
                return $this->redirectToRoute('app_login');
            }
        }
        // Ici un problème se pose dans le token
        $this->addFlash('danger', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }
}