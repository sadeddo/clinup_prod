<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\Reservation;
use App\Service\EmailSender;
use App\Entity\CommentPresta;
use App\Entity\DemandeService;
use App\Form\CommentPrestaType;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use App\Repository\CommentPrestaRepository;
use App\Repository\NotificationsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\DemandeServiceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/comment/presta')]
class CommentPrestaController extends AbstractController
{
    #[Route('/', name: 'app_comment_presta_index', methods: ['GET'])]
    public function index(CommentPrestaRepository $commentPrestaRepository, Security $security): Response
    {
        return $this->render('comment_presta/index.html.twig', [
            'comment_prestas' => $commentPrestaRepository->findAll(),
        ]);
    }

    #[Route('/{id}/{idDemande}/new', name: 'app_comment_presta_new', methods: ['GET', 'POST'])]
    public function new($id,$idDemande,Request $request, EntityManagerInterface $entityManager,Security $security,CommentPrestaRepository $commentPrestaRepository,ReservationRepository $reservationRepository,EmailSender $notificationService, RecommendationService $recommendationService): Response
    {
        $presta = $entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
        $commentPrestum = new CommentPresta();
        $form = $this->createForm(CommentPrestaType::class, $commentPrestum);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            date_default_timezone_set('Europe/Paris');
            $commentPrestum->setClient($security->getUser());
            $commentPrestum->setPrestataire($entityManager->getRepository(User::class)->findOneBy(['id' => $id]));
            $commentPrestum->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($commentPrestum);
            $entityManager->flush();
            

            $recommendationService->handleRecommendation($commentPrestum, $notificationService);

            return $this->redirectToRoute('app_consulter_profileP', ['id' => $id,'idReservation'=>$idDemande], Response::HTTP_SEE_OTHER);
        }
        $average = $commentPrestaRepository->getAverageNoteForPrestataire($id);
        return $this->render('profile/consulter.html.twig', [
            'presta'=> $user,
            'comment_prestum' => $commentPrestum,
            "userpic" => $security->getUser(),
            'form' => $form,
            'average' => $average,
            'demande' => $entityManager->getRepository(Reservation::class)->findOneBy(['id' => $idDemande ]),
            'cible' => 'comment',
            'idDemande' => $idDemande,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_presta_show', methods: ['GET'])]
    public function show(CommentPresta $commentPrestum): Response
    {
        return $this->render('comment_presta/show.html.twig', [
            'comment_prestum' => $commentPrestum,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_presta_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CommentPresta $commentPrestum, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentPrestaType::class, $commentPrestum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_presta_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment_presta/edit.html.twig', [
            'comment_prestum' => $commentPrestum,
            'form' => $form,
            'nbrNotif' => $this->notificationsRepository->countUnreadByUser($security->getUser()->getId())
        ]);
    }

    #[Route('/{id}', name: 'app_comment_presta_delete', methods: ['POST'])]
    public function delete(Request $request, CommentPresta $commentPrestum, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commentPrestum->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commentPrestum);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comment_presta_index', [], Response::HTTP_SEE_OTHER);
    }
}
