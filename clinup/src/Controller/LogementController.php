<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/logement')]
class LogementController extends AbstractController
{
    #[Route('/', name: 'app_logement_index', methods: ['GET'])]
    public function index(LogementRepository $logementRepository, Security $security): Response
    {
        $icalContent = file_get_contents('https://www.airbnb.fr/calendar/ical/1115296086788167902.ics?s=ce7487757e36eacd4bcad37adde40208');
        dd($icalContent);
        return $this->render('logement/index.html.twig', [
            'logements' => $logementRepository->findBy(['hote' => $security->getUser()]),
        ]);
    }

    #[Route('/new', name: 'app_logement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logement->setHote($security->getUser());
            $file = $form->get('img')->getData();
            if ($file) {
                // Générez un nom de fichier unique
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // Déplacez le fichier dans le répertoire où sont stockées les brochures
                try {
                    $file->move(
                        $this->getParameter('upload_directory_logement'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // ... gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }
        
                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $logement->setImg($fileName);
            }
            $entityManager->persist($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Votre logement est ajouté avec succès!
            Facilitez vos réservations en spécifiant toutes les tâches pour ce logement');
            return $this->redirectToRoute('app_task_index', ['id' => $logement->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/new.html.twig', [
            'logement' => $logement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_logement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifiez si une nouvelle image a été soumise
            $file = $form->get('img')->getData();
            if ($file) {
                // Supprimer l'ancienne image si elle existe
                $oldFileName = $logement->getImg();
            if ($oldFileName && file_exists($this->getParameter('upload_directory_logement') . '/' . $oldFileName)) {
                unlink($this->getParameter('upload_directory_logement') . '/' . $oldFileName);
            }

                // Générez un nom de fichier unique pour la nouvelle image
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // Déplacez le fichier dans le répertoire où sont stockées les images
                try {
                    $file->move(
                        $this->getParameter('upload_directory_logement'), // Répertoire de destination
                        $fileName // Nouveau nom du fichier
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si quelque chose se passe pendant le téléchargement du fichier
                }

                // Mettez à jour l'entité pour stocker le nouveau nom du fichier
                $logement->setImg($fileName);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Votre logement a été modifié avec succès !');
            return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('logement/edit.html.twig', [
            'logement' => $logement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_logement_delete', methods: ['POST','GET'])]
    public function delete(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        // Supprimer d'abord toutes les tâches et images associées au logement
            foreach ($logement->getTasks() as $task) {
                // Supprimer toutes les images associées à la tâche
                foreach ($task->getImgTasks() as $imgTask) {
                    $entityManager->remove($imgTask);
                }

                // Ensuite, supprimer la tâche elle-même
                $entityManager->remove($task);
            }

            $entityManager->remove($logement);
            $entityManager->flush();
        return $this->redirectToRoute('app_logement_index', [], Response::HTTP_SEE_OTHER);
    }
}
