<?php

namespace App\Controller;

use DateTime;
use App\Entity\Contact;
use App\Form\ContactType;
use App\Service\EmailSender;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contact_index', methods: ['GET'])]
    public function index(ContactRepository $contactRepository): Response
    {
        return $this->render('contact/index.html.twig', [
            'contacts' => $contactRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, EmailSender $emailSender): Response
{
    $contact = new Contact();
    $form = $this->createForm(ContactType::class, $contact);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Persiste le contact dans la base de données
        $entityManager->persist($contact);
        $entityManager->flush();

        // Envoie l'email
        $emailSender->sendEmail(
            'contact@clinup.fr', // Remplacez par votre email
            'Nouvelle demande de contact',
            'email/contact.html.twig',
            [
                'contact' => $contact,
                'name' => $contact->getName(),
                'email' => $contact->getEmail(),
                'message' => $contact->getMessage(),
            ]
        );

        $this->addFlash('success', 'Votre message a été envoyé avec succès');
        return $this->redirectToRoute('app_contact_new', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('contact/new.html.twig', [
        'contact' => $contact,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
    }
}
