<?php

namespace App\Controller;

use App\Entity\Video;
use App\Form\VideoType;
use App\Entity\Postuler;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class VideoController extends AbstractController
{
    #[Route('/video/{id}/upload', name: 'app_video_upload')]
    public function index(Reservation $reservation, Request $request, EntityManagerInterface $entityManager, Security $security, ReservationRepository $reservationRepository): Response
    {
        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if ($file) {
                $filename = uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('videos_directory'),
                        $filename
                    );
                    $video->setFilePath($filename);
                    $entityManager->persist($video);
                    $entityManager->flush();

                    $this->addFlash('success', 'Vidéo uploadée avec succès !');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la vidéo.');
                }
                return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()], Response::HTTP_SEE_OTHER);
            }
        }

        // Vérifier si l'utilisateur a déjà postulé
        $postulation = $entityManager->getRepository(Postuler::class)
            ->findOneBy(['reservation' => $reservation, 'prestataire' => $security->getUser()]);

        return $this->render('reservation/showP.html.twig', [
            'form' => $form->createView(),
            'cible' => 'ajouterVideo',
            'idReservation' => $reservation->getId(),
            'reservation' => $reservationRepository->findOneBy(['id' => $reservation->getId()]),
            'hasApplied' => $postulation,
        ]);
    }
}
