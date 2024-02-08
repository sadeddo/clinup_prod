<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Entity\Reservation;
use App\Repository\TaskRepository;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{
    #[Route('/tache/{id}/liste', name: 'app_task_index', methods: ['GET'])]
    public function index($id,TaskRepository $taskRepository,LogementRepository $logementRepository): Response
    {
        return $this->render('task/index.html.twig', [
            'tasks' => $taskRepository->findBy(['logement' => $logementRepository->findBy(['id' => $id])]),
            'logement' => $logementRepository->findOneBy(['id' => $id]),
        ]);
    }

    #[Route('/tache/{id}/ajouter', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new($id,Request $request, EntityManagerInterface $entityManager,LogementRepository $logementRepository): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setLogement($logementRepository->findOneBy(['id' => $id]));

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_task_index', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/tache/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/tache/{id}/modifier', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_task_index', ['id' => $task->getLogement()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/tache/{id}/delete', name: 'app_task_delete', methods: ['POST','GET'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        foreach ($task->getImgTasks() as $imgTask) {
            $entityManager->remove($imgTask);
        }
            $entityManager->remove($task);
            $entityManager->flush();

        return $this->redirectToRoute('app_task_index', ['id' => $task->getLogement()->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/tache/{id}/statut', name: 'app_task_modifier_statut', methods: ['GET', 'POST'])]
    public function modifierStatut(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if ($task->IsStatut() == '0' ||  $task->IsStatut() == NULL) {
            $task->setStatut('1');
            $entityManager->persist($task);
            $entityManager->flush();
        }elseif($task->IsStatut() == '1'){
            $task->setStatut('0');
            $entityManager->persist($task);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_task_index', ['id' => $task->getLogement()->getId()], Response::HTTP_SEE_OTHER);
    }
    //pour le prestataire
    #[Route('/tâches/{id}/listeeeee', name: 'app_task_listeee', methods: ['GET'])]
    public function indexP($id,TaskRepository $taskRepository,LogementRepository $logementRepository,Reservation $reservation): Response
    {
        return $this->render('task/taskP.html.twig', [
            'tasks' => $taskRepository->findBy(['logement' => $reservation->getLogement()]),
            'cible' => '',
            'idReservation' => $id
        ]);
    }
    
}
