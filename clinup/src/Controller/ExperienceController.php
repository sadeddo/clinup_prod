<?php

namespace App\Controller;

use App\Entity\Justif;
use App\Form\ProfileType;
use App\Entity\Experience;
use App\Form\ExperienceType;
use App\Repository\ExperienceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/experience')]
class ExperienceController extends AbstractController
{
    #[Route('/', name: 'app_experience_index', methods: ['GET'])]
    public function index(ExperienceRepository $experienceRepository,Security $security): Response
    {
        $prestataire = $security->getUser();
        return $this->render('profile/profileP.html.twig', [
            'experiences' => $experienceRepository->findBy([ 'prestataire' => $prestataire]),
        ]);
    }

    #[Route('/new', name: 'app_experience_new', methods: ['GET', 'POST'])]
    public function new(Request $request,EntityManagerInterface $entityManager,Security $security,ExperienceRepository $experienceRepository): Response
    {
         // Récupérez l'utilisateur actuel
         $user = $security->getUser();
         $anPic = $user->getPicture();
         // Créez le formulaire
         $formInfo = $this->createForm(ProfileType::class, $user);
         $formInfo->handleRequest($request);
 
         if ($formInfo->isSubmitted() && $formInfo->isValid()) {
             // Obtenez le fichier image soumis
             $PictureFile = $formInfo->get('picture')->getData();
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
             return $this->redirectToRoute("app_modifier_profile_profileP");
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
                 return $this->redirectToRoute("app_modifier_profile_profileP");
             } else {
                 $this->addFlash(
                     'warning',
                     'Le mot de passe renseigné est incorrect.'
                 );
             }
         }
        $prestataire = $security->getUser();
        $experience = new Experience();
        $form = $this->createForm(ExperienceType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $experience->setPrestataire($prestataire);
            $entityManager->persist($experience);
            $entityManager->flush();

            return $this->redirectToRoute('app_modifier_profile_profileP', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/profileP.html.twig', [
            'experience' => $experience,
            "user" => $prestataire,
            'formExp' => $form,
            'cible' => "addExp",
            'form'=> $formInfo,
            "formpass" => $formpass,
            'experiences' => $experienceRepository->findBy([ 'prestataire' => $user]),
            'formFile' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_experience_show', methods: ['GET'])]
    public function show(Experience $experience): Response
    {
        return $this->render('experience/show.html.twig', [
            'experience' => $experience,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_experience_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Experience $experience, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExperienceType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_experience_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('experience/edit.html.twig', [
            'experience' => $experience,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_experience_delete', methods: ['POST'])]
    public function delete(Request $request, Experience $experience, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$experience->getId(), $request->request->get('_token'))) {
            $entityManager->remove($experience);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_experience_index', [], Response::HTTP_SEE_OTHER);
    }
}
