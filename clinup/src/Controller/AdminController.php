<?php

namespace App\Controller;

use App\Service\EmailSender;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $prestataireRepository,Security $security,NotificationService $notificationService): Response
    {
        $prestataires = $prestataireRepository->findByRole('ROLE_PRESTATAIRE');
        return $this->render('admin/index.html.twig', [
            'prestataires' => $prestataires
        ]);
    }
    #[Route('/admin/{id}/valider', name: 'app_admin_valider')]
    public function valider($id,UserRepository $prestataireRepository,EntityManagerInterface $entityManager,NotificationService $notifService) 
    {
        $prestataire = $prestataireRepository->findOneBy(['id' => $id]);
        if ($prestataire->isIsVerified()) {
            $prestataire->setIsVerified(false);
        }else{
            $prestataire->setIsVerified(true);
            $notifService->createNotification(
                $prestataire,
                'Votre profil a été validé',
                '/modifier/profile'
            );
        }
        $entityManager->persist($prestataire);
        $entityManager->flush();
        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/admin/{id}/consulter', name: 'app_admin_consulter')]
    public function consulter($id,UserRepository $prestataireRepository,EntityManagerInterface $entityManager) 
    {
        $prestataire = $prestataireRepository->findOneBy(['id' => $id]);
       
        return $this->render('admin/consulter.html.twig', [
            'prestataire' => $prestataire
        ]);
    }
}
