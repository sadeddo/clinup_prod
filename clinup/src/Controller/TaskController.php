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
            'cible' => ''
        ]);
    }

    #[Route('/tache/{id}/ajouter', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new($id,Request $request, EntityManagerInterface $entityManager,LogementRepository $logementRepository,TaskRepository $taskRepository): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setLogement($logementRepository->findOneBy(['id' => $id]));
            $file = $form->get('img')->getData();
            if ($file) {
                // Générez un nom de fichier unique
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // Déplacez le fichier dans le répertoire où sont stockées les brochures
                try {
                    $file->move(
                        $this->getParameter('upload_directory_task_hote'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // ... gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }
        
                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $task->setImg($fileName);
            }
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche que vous avez ajoutée a été enregistrée avec succès !');
            return $this->redirectToRoute('app_task_index', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $taskRepository->findBy(['logement' => $logementRepository->findBy(['id' => $id])]),
            'logement' => $logementRepository->findOneBy(['id' => $id]),
            'task' => $task,
            'form' => $form,
            'cible' => 'task'
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
    public function edit($id,Request $request, Task $task, EntityManagerInterface $entityManager,LogementRepository $logementRepository,TaskRepository $taskRepository): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('img')->getData();
            if ($file) {
                // Générez un nom de fichier unique
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // Déplacez le fichier dans le répertoire où sont stockées les brochures
                try {
                    $file->move(
                        $this->getParameter('upload_directory_task_hote'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // ... gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }
        
                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $task->setImg($fileName);
            } // Ajoutez un else if pour conserver l'image existante si aucune nouvelle image n'est soumise
            else if ($task->getImg() !== null) {
                // Laissez le nom de l'image existant tel quel
                // $task->setImg($existingFileName);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Votre modification a été enregistrée avec succès !');
            return $this->redirectToRoute('app_task_index', ['id' => $task->getLogement()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $taskRepository->findBy(['logement' => $logementRepository->findBy(['id' => $task->getLogement()->getId()])]),
            'logement' => $task->getLogement(),
            'task' => $task,
            'form' => $form,
            'cible' => 'taskModifier'
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

            $this->addFlash('success', 'Votre modification a été enregistrée avec succès !');
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
