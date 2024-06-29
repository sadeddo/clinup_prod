<?php

namespace App\Controller;

use App\Entity\ImgTask;
use App\Entity\Postuler;
use App\Form\ImgTaskType;
use App\Entity\Reservation;
use App\Repository\TaskRepository;
use App\Repository\VideoRepository;
use App\Repository\ImgTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/img')]
class ImgTaskController extends AbstractController
{
    #[Route('/', name: 'app_img_task_index', methods: ['GET'])]
    public function index(ImgTaskRepository $imgTaskRepository): Response
    {
        return $this->render('img_task/index.html.twig', [
            'img_tasks' => $imgTaskRepository->findAll(),
        ]);
    }

    #[Route('/{idReservation}/{idTask}/ajouter', name: 'app_img_task_new', methods: ['GET', 'POST'])]
    public function new($idReservation,$idTask,Request $request, EntityManagerInterface $entityManager,TaskRepository $TaskRepository,ReservationRepository $reservationRepository,Security $security,VideoRepository $videoRepository): Response
    {
        $imgTask = new ImgTask();
        $form = $this->createForm(ImgTaskType::class, $imgTask);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imgTask->setTask($TaskRepository->findOneBy(['id' => $idTask]));
            //enregistrer img
            $file = $form->get('filePath')->getData();
            if ($file) {
                // Générez un nom de fichier unique
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
        
                // Déplacez le fichier dans le répertoire où sont stockées les brochures
                try {
                    $file->move(
                        $this->getParameter('upload_directory_task'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // ... gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }
        
                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $imgTask->setFilePath($fileName);
            }
            $entityManager->persist($imgTask);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_show', ['id' => $idReservation ], Response::HTTP_SEE_OTHER);
        }
          //verifier si l'utilisateur à deja postuler
        $postulation = $entityManager->getRepository(Postuler::class)
        ->findOneBy(['reservation' => $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idReservation]), 'prestataire' => $security->getUser()]);
        $video = $videoRepository->findOneBy(['reservation' =>  $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idReservation])]);
        return $this->render('reservation/showP.html.twig', [
            'img_task' => $imgTask,
            'video' => $video,
            'form' => $form,
            'cible' => 'ajouter',
            'idReservation' => $idReservation,
            'reservation' => $reservationRepository->findOneBy(['id' => $idReservation]),
            'hasApplied' => $postulation
            
        ]);
    }

    #[Route('/{id}', name: 'app_img_task_show', methods: ['GET'])]
    public function show(ImgTask $imgTask): Response
    {
        return $this->render('img_task/show.html.twig', [
            'img_task' => $imgTask,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_img_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ImgTask $imgTask, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImgTaskType::class, $imgTask);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_img_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('img_task/edit.html.twig', [
            'img_task' => $imgTask,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_img_task_delete', methods: ['POST'])]
    public function delete(Request $request, ImgTask $imgTask, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$imgTask->getId(), $request->request->get('_token'))) {
            $entityManager->remove($imgTask);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_img_task_index', [], Response::HTTP_SEE_OTHER);
    }
}
