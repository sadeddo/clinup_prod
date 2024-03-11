<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Entity\Reservation;
use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use App\Repository\ExperienceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentPrestaRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    private function editProfil($page,Request $request, Security $security, EntityManagerInterface $entityManager,UserPasswordHasherInterface $hasher,LoggerInterface $logger,ExperienceRepository $experienceRepository): Response
    {
        // Récupérez l'utilisateur actuel
        $user = $security->getUser();
        $anPic = $user->getPicture();
        // Créez le formulaire
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Obtenez le fichier image soumis
            $PictureFile = $form->get('picture')->getData();
            // Vérifiez si un nouveau fichier a été soumis
            if ($PictureFile) {
                $originalFilename = pathinfo($PictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $user->getFirstname()."_".$user->getLastname().'-'.uniqid().'.'.$PictureFile->guessExtension();
        
                // Déplacez le nouveau fichier dans le répertoire de téléchargement
                try {
                    $PictureFile->move(
                        $this->getParameter('upload_directory_img'),
                        $newFilename
                    );
                    if ($anPic) {
                        $pathToFile = $this->getParameter('upload_directory_img').'/'.$anPic;
                        if (file_exists($pathToFile)) {
                            unlink($pathToFile);
                        }
                    }
            
                    // Mettez à jour l'entité utilisateur avec le nouveau nom de fichier
                    $user->setPicture($newFilename);
                } catch (FileException $e) {
                    // Gérez l'exception si le fichier ne peut pas être déplacé
                    $logger->error('Impossible d\'enregistrer l\'image : ' . $e->getMessage());
                }
        
                // Supprimez l'ancien fichier s'il existe
            }
        
            // Enregistrez les autres modifications
            $entityManager->persist($user);
                $entityManager->flush();
            // Ajoutez un message flash et redirigez
            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('app_modifier_profile_'.$page);
        }
        
        /////////
        $user = $security->getUser();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        #création d'un form pour modifier le mot de passe
        $formpass = $this->createFormBuilder();
        $formpass
        #l'ancien mdp
        ->add('oldpassword', PasswordType::class, [
            "attr" => [
                "placeholder" => "Ancien mot de passe",
                'class' => 'form-control']
            ])
        #le nouveau mdp with confirmation
        ->add('plainpassword', RepeatedType::class, array(
            'type' => PasswordType::class,
            'invalid_message' => 'Le mot de passe et sa confirmation doivent être identiques.',
            'options' => ['attr' => [
            "placeholder" => "Nouveau mot de passe",
            'class' => 'form-control']],
            'required' => true,
            'first_options'  => ['label' => ' new Password'],
            'second_options' => ['label' => 'Confirm Password'],
            
            ));
            $formpass = $formpass->getForm();
        $formpass->handleRequest($request);
        if ($formpass->isSubmitted() && $formpass->isValid()) {
            #comparer le mdp entré et l'ancien mdp
            if ($hasher->isPasswordValid($user, $formpass->get('oldpassword')->getData())) {
                $user->setPassword(
                    #stocker le nouveau mdp 
                    $hasher->hashPassword(
                        $user,
                        $formpass->get('plainpassword')->getData()
                    )
                );
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('successp', 'Votre mot de passe a été mis à jour.');
                return $this->redirectToRoute('app_modifier_profile_'.$page);
            } else {
                $this->addFlash(
                    'warning',
                    'Le mot de passe renseigné est incorrect.'
                );
            }
        }

        // Affichez le formulaire dans un template
        return $this->render('profile/'.$page.'.html.twig', [
            'form' => $form->createView(),
            'formpass' => $formpass->createView(),
            'user' => $user,
            'cible' => '',
            'experiences' => $experienceRepository->findBy([ 'prestataire' => $user]),
        ]);
    }
    #[Route('/profile/modifier', name: 'app_modifier_profile_profileH', methods: ['GET', 'POST'])]
    public function modifierH(Request $request, Security $security, EntityManagerInterface $entityManager,UserPasswordHasherInterface $hasher, LoggerInterface $logger,ExperienceRepository $experienceRepository): Response
    {
        return $this->editProfil('profileH',$request,$security,$entityManager,$hasher,$logger, $experienceRepository);
    }

    #[Route('/modifier/profile', name: 'app_modifier_profile_profileP', methods: ['GET', 'POST'])]
    public function modifierP(Request $request, Security $security, EntityManagerInterface $entityManager,UserPasswordHasherInterface $hasher, LoggerInterface $logger, ExperienceRepository $experienceRepository): Response
    {
        return $this->editProfil('profileP',$request,$security,$entityManager,$hasher,$logger,$experienceRepository);
    }
    //afficher profile prestatire
    #[Route('/consulterP/{id}/{idReservation}/profile', name: 'app_consulter_profileP', methods: ['GET', 'POST'])]
    public function consulterP($id,$idReservation, UserRepository $userRepository,CommentPrestaRepository $commentPrestaRepository, Security $security, EntityManagerInterface $entityManager): Response
    {
        $prestataire = $userRepository->findOneBy(['id'=> $id]);
        $average = $commentPrestaRepository->getAverageNoteForPrestataire($prestataire->getId());
        
        return $this->render('profile/consulter.html.twig', [
            'average' => $average,
            'demande' => $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idReservation ]),
            'prestataire' => $prestataire,
            'idReservation' => $idReservation,
            'presta' => $prestataire,
            'cible' => '',
        ]);
    }
}