<?php

namespace App\Controller;

use App\Entity\Justif;
use App\Form\JustifType;
use App\Form\ProfileType;
use App\Repository\ExperienceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class JustificatifController extends AbstractController
{
    #[Route('/justificatif', name: 'app_justificatif')]
    public function index(Request $request,Security $security): Response
    {
        $id = $security->getUser()->getId();
        return $this->render('profile/profileP.html.twig', [
            'controller_name' => 'JustificatifController',
            'cible' => '',
            'idUser' => $id ,
            "user" => $security->getUser(),
            "nomUser" => $security->getUser()->getFirstname().' '.$security->getUser()->getLastname(),
        ]);
    }
    private function generateFileName($user, $type, $file)
    {
        date_default_timezone_set('Europe/Paris');
        $date = date('dmyhi');
        $fileName = $user->getFirstname() . '-' . $type . '-' . $date . '.' . $file->guessExtension();

        return $fileName;
    }
    private function removeFile($filePath)
    {
        $uploadDir = $this->getParameter('upload_directory_doc');
        $fullPath = $uploadDir . '/' . $filePath;

        if ($filePath && file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    private function justif($type,Request $request,EntityManagerInterface $entityManager,Security $security,ExperienceRepository $experienceRepository): Response
    {
        $prestataire = $security->getUser();
        $existingJustificatif = $entityManager->getRepository(Justif::class)->findOneBy([
            'prestataire' => $prestataire, 
            'type' => $type
        ]);
        $form = $this->createForm(JustifType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('filePath')->getData();
            if ($file !== null) {
                if ($existingJustificatif) {
                    // Supprimez l'ancien fichier si existant
                    $this->removeFile($existingJustificatif->getFilePath());
                }
                $fileName = $this->generateFileName($prestataire, $type, $file);
                try {
                    $file->move(
                        $this->getParameter('upload_directory_doc'),
                        $fileName
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }
                if ($existingJustificatif) {
                     // Supprimez l'ancien fichier si existant
                    // Mise à jour du justificatif existant
                    $existingJustificatif->setFilePath($fileName);
                } else {
                    // Créer un nouveau justificatif
                    $justificatif = new Justif();
                    $justificatif->setFilePath($fileName);
                    $justificatif->setType($type);
                    $justificatif->setPrestataire($prestataire);
                    $entityManager->persist($justificatif);
                }
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_modifier_profile_profileP', [], Response::HTTP_SEE_OTHER);
        }
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
        return $this->render('profile/profileP.html.twig', [
            'cible' => $type.'Canvas',
            'formFile' => $form,
            'idUser' => $prestataire->getId(),
            "user" => $prestataire,
            "nomUser" =>  $prestataire->getFirstname().' '. $prestataire->getLastname(),
            'form'=> $formInfo,
            "formpass" => $formpass,
            'experiences' => $experienceRepository->findBy([ 'prestataire' => $user]),
        ]);
    }

    #[Route('/justificatif/identity', name: 'app_justificatif_identity')]
    public function identity(Request $request,EntityManagerInterface $entityManager,Security $security,ExperienceRepository $experienceRepository): Response
    {
       return $this->justif('identity',$request,$entityManager,$security,$experienceRepository);
    }
    #[Route('/justificatif/domicile', name: 'app_justificatif_domicile')]
    public function domicile(Request $request,EntityManagerInterface $entityManager,Security $security,ExperienceRepository $experienceRepository): Response
    {
       return $this->justif('domicile',$request,$entityManager,$security,$experienceRepository);
    }
    #[Route('/justificatif/vitale', name: 'app_justificatif_vitale')]
    public function vitale(Request $request,EntityManagerInterface $entityManager,Security $security,ExperienceRepository $experienceRepository): Response
    {
       return $this->justif('vitale',$request,$entityManager,$security,$experienceRepository);
    }
    //download
    private function download($type,EntityManagerInterface $entityManager,Security $security): response
    {
        // Recherchez le document de type spécifié pour l'utilisateur actuel
        $justificatif = $entityManager->getRepository(Justif::class)->findOneBy([
            'prestataire' => $security->getUser(),
            'type' => $type
        ]);

        if (!$justificatif) {
            $this->addFlash('error', 'Document non trouvé.');
            return $this->redirectToRoute('app_justificatif');
        }

        // Obtenez le chemin complet du fichier
        $filePath = $this->getParameter('upload_directory_doc') . '/' . $justificatif->getFilePath();

        // Vérifiez si le fichier existe
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Fichier non trouvé.');
            return $this->redirectToRoute('app_justificatif');
        }

        // Créez une réponse pour le téléchargement du fichier
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );

        return $response;
    }
    #[Route('/justificatif/identity/download', name: 'app_download_identity')]
    public function identityDownload(EntityManagerInterface $entityManager,Security $security): Response
    {
       return $this->download('identity',$entityManager,$security);
    }
    #[Route('/justificatif/domicile/download', name: 'app_download_domicile')]
    public function domicileDownload(EntityManagerInterface $entityManager,Security $security): Response
    {
       return $this->download('domicile',$entityManager,$security);
    }
    #[Route('/justificatif/vitale/download', name: 'app_download_vitale')]
    public function vitaleDownload(EntityManagerInterface $entityManager,Security $security): Response
    {
       return $this->download('vitale',$entityManager,$security);
    }
}
