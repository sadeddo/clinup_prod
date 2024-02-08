<?php

namespace App\Controller;

use App\Entity\Justif;
use App\Form\JustifType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class JustificatifController extends AbstractController
{
    #[Route('/justificatif', name: 'app_justificatif')]
    public function index(Request $request,Security $security): Response
    {
        $id = $security->getUser()->getId();
        return $this->render('justificatif/index.html.twig', [
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
    private function justif($type,Request $request,EntityManagerInterface $entityManager,Security $security): Response
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
            return $this->redirectToRoute('app_justificatif', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('justificatif/index.html.twig', [
            'cible' => $type.'Canvas',
            'form' => $form,
            'idUser' => $prestataire->getId(),
            "user" => $prestataire,
            "nomUser" =>  $prestataire->getFirstname().' '. $prestataire->getLastname(),
        ]);
    }

    #[Route('/justificatif/identity', name: 'app_justificatif_identity')]
    public function identity(Request $request,EntityManagerInterface $entityManager,Security $security): Response
    {
       return $this->justif('identity',$request,$entityManager,$security);
    }
    #[Route('/justificatif/domicile', name: 'app_justificatif_domicile')]
    public function domicile(Request $request,EntityManagerInterface $entityManager,Security $security): Response
    {
       return $this->justif('domicile',$request,$entityManager,$security);
    }
    #[Route('/justificatif/vitale', name: 'app_justificatif_vitale')]
    public function vitale(Request $request,EntityManagerInterface $entityManager,Security $security): Response
    {
       return $this->justif('vitale',$request,$entityManager,$security);
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
