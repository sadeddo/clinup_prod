<?php

namespace App\Controller;

use DateTime;
use App\Entity\Dispo;
use App\Form\DispoType;
use App\Repository\DispoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/availabilities')]
class DispoController extends AbstractController
{
    #[Route('/', name: 'app_dispo')]
    public function index(DispoRepository $DispoRepository,Request $request,EntityManagerInterface $entityManager,Security $security): Response
    {
        $prestataire = $security->getUser();
        $rdv[] = $prestataire->getDispos();
        $events= $rdv[0];
        $rdvs = [];
        foreach($events as $event){
            $rdvs[] = [
                'id' =>$event->getId(),
                'start' =>$event->getDate()->format('Y-m-d').' '.$event->getDtStart(),
                'end' =>$event->getDate()->format('Y-m-d').' '.$event->getDtEnd(),
                'color' => '#FF385C',
            ];
        }
        $data = json_encode($rdvs);
        return $this->render('dispo/index.html.twig', [
            'data' => $data,
            'cible' => '',
            "user" => $prestataire,
        ]);
    }
    private function createWeeklyDispos($dayOfWeek, Request $request, EntityManagerInterface $entityManager, Security $security){
        $prestataire = $security->getUser();
        $rdv[] = $prestataire->getDispos();
        $events= $rdv[0];
        $rdvs = [];
        foreach($events as $event){
            $rdvs[] = [
                'id' =>$event->getId(),
                'start' =>$event->getDate()->format('Y-m-d').' '.$event->getDtStart(),
                'end' =>$event->getDate()->format('Y-m-d').' '.$event->getDtEnd(),
                'color' => '#FF385C',
            ];
        }
        $data = json_encode($rdvs);
        $availability = new Dispo();
        $form = $this->createForm(DispoType::class, $availability);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dtStart = $availability->getDtStart();
            $dtEnd = $availability->getDtEnd();
            // Assurez-vous que l'heure de fin est après l'heure de début
            if ($dtEnd <= $dtStart) {
                $this->addFlash('error', 'L\'heure de fin doit être postérieure à l\'heure de début.');
                return $this->render('dispo/index.html.twig', [
                    'form' => $form->createView(),
                    'cible' => 'modal' . ucfirst(strtolower($dayOfWeek)),
                    'data' => $data,
                    "user" => $security->getUser(),
                ]);
            }

            date_default_timezone_set('Europe/Paris');
            $date = new DateTime('first '. $dayOfWeek .' January ' . date('Y'));
            while ($date->format('Y') == date('Y')) {
                $availability = new Dispo();
                $availability->setDate($date);
                $availability->setStatut('dispo');
                $availability->setPrestataire($security->getUser());
                $availability->setDtStart($form->get('dtStart')->getData());
                $availability->setDtEnd($form->get('dtEnd')->getData());
                $entityManager->persist($availability);
                $date->modify('+1 week');
                $entityManager->flush();
            }
            return $this->redirectToRoute('app_dispo', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('dispo/index.html.twig', [
            'form' => $form,
            'cible' => 'modal' . ucfirst(strtolower($dayOfWeek)),
            'data' => $data,
            "user" => $security->getUser(),
        ]);
    }
    #[Route('/lundis', name: 'app_availabilities_lundis', methods: ['GET', 'POST'])]
    public function monday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Monday', $request, $entityManager, $security);
    }

    #[Route('/mardis', name: 'app_availabilities_mardis', methods: ['GET', 'POST'])]
    public function tuesday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Tuesday', $request, $entityManager, $security);
    }

    #[Route('/mercredis', name: 'app_availabilities_mercredis', methods: ['GET', 'POST'])]
    public function wednesday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Wednesday', $request, $entityManager, $security);
    }

    #[Route('/jeudis', name: 'app_availabilities_jeudis', methods: ['GET', 'POST'])]
    public function thursday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Thursday', $request, $entityManager, $security);
    }

    #[Route('/vendredis', name: 'app_availabilities_vendredis', methods: ['GET', 'POST'])]
    public function friday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Friday', $request, $entityManager, $security);
    }

    #[Route('/samedis', name: 'app_availabilities_samedis', methods: ['GET', 'POST'])]
    public function saturday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Saturday', $request, $entityManager, $security);
    }

    #[Route('/dimanches', name: 'app_availabilities_dimanches', methods: ['GET', 'POST'])]
    public function sunday(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        return $this->createWeeklyDispos('Sunday', $request, $entityManager, $security);
    }
    #[Route('/edit/{id}', name: 'app_availabilities_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dispo $availability, DispoRepository $DispoRepository,EntityManagerInterface $entityManager,int $id,Security $security): Response
    {
        $event = $entityManager->getRepository(Dispo::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException(
                'No event found for id '.$id
            );
        }

        if ($request->isXmlHttpRequest()) {
            $eventData = array(
                'start' =>$event->getDate()->format('Y-m-d').' '.$event->getDtStart(),
                'end' =>$event->getDate()->format('Y-m-d').' '.$event->getDtEnd(),
                'color' => '#FF385C',
            );

            return new JsonResponse($eventData);
        }

        $form = $this->createForm(DispoType::class, $availability);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($availability);
            $entityManager->flush();

            return $this->redirectToRoute('app_dispo', [], Response::HTTP_SEE_OTHER);
        }
        ///
        $prestataire = $security->getUser();
        $rdv[] = $prestataire->getDispos();
        $events= $rdv[0];
        $rdvs = [];
        foreach($events as $event){
            $rdvs[] = [
                'id' =>$event->getId(),
                'start' =>$event->getDate()->format('Y-m-d').' '.$event->getDtStart(),
                'end' =>$event->getDate()->format('Y-m-d').' '.$event->getDtEnd(),
                'color' => '#FF385C',
            ];
        }
        $data = json_encode($rdvs);

        return $this->render('dispo/index.html.twig', [
            'event' => $event,
            'form' => $form,
            'data' => $data,
            'cible' => 'edit',
            "user" => $prestataire,

        ]);
    }


}
