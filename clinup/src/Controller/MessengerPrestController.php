<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\Messages;
use App\Form\MessageType;
use App\Entity\Conversation;
use App\Service\EmailSender;
use App\Form\MessageFileType;
use App\Repository\MessagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use App\Repository\NotificationsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MessengerPrestController extends AbstractController
{
  
    #[Route('/chat', name: 'app_chat', methods: ['GET'])]
    public function index(Security $security, EntityManagerInterface $entityManager,ConversationRepository $conversationRepository): Response
    {
        $id = $security->getUser()->getId();
        $qb = $entityManager->createQueryBuilder();
        $qb->select('c')
        ->from(Conversation::class, 'c')
        ->where($qb->expr()->orX(
            $qb->expr()->eq('c.participant1', ':id'),
            $qb->expr()->eq('c.participant2', ':id')
        ))
        ->setParameter('id', $id);
        $all[] = $qb->getQuery()->getResult();
        $conversations= $all[0];
        $conversationsTable = [];
        foreach($conversations as $conversation){
            $lastMessages = $entityManager
            ->getRepository(Messages::class)
            ->createQueryBuilder('d')
            ->where('d.idConversation = :id')
            ->setParameter('id', $conversation->getId())
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
            if ($conversation->getParticipant2()->getId() != $id){                
                if (!empty($lastMessages)) {
                    $lastMessage = $lastMessages[0];
                    $conversationsTable[] = [
                    'id' => $conversation->getId(),
                    'nom' => $conversation->getParticipant2()->getFirstname().' '.$conversation->getParticipant2()->getLastname(),
                    'picture' => $conversation->getParticipant2()->getPicture(),
                    'idRecipiant' => $conversation->getParticipant2()->getId(),
                    'lastMessage' => $lastMessage->getContent(),
                    'type' => $lastMessage->getType(),
                    'CreatedAt' => $lastMessage->getCreatedAt(),
                ];}
            }else{
                if (!empty($lastMessages)) {
                    $lastMessage = $lastMessages[0];
                    $conversationsTable[] = [
                    'id' => $conversation->getId(),
                    'nom' => $conversation->getParticipant1()->getFirstname().' '.$conversation->getParticipant1()->getLastname(),
                    'picture' => $conversation->getParticipant1()->getPicture(),
                    'idRecipiant' => $conversation->getParticipant1()->getId(),
                    'lastMessage' => $lastMessage->getContent(),
                    'type' => $lastMessage->getType(),
                    'CreatedAt' => $lastMessage->getCreatedAt(),
                ];}
            }
            
        }
        return $this->render('messengerPrest/index.html.twig', [
            'conversations' => $conversationsTable,
            'cible' => "",
            "user" => $security->getUser(),
        ]);
    }
    #[Route('/chat/{id}/{idrecipiant}/message', name: 'app_chat_show', methods: ['GET','POST'])]
    public function show(int $id,int $idrecipiant,Security $security,MessagesRepository $messagesRepository,EntityManagerInterface $entityManager,Request $request,EmailSender $notificationService): Response
    {
        //Ajout d'image
        $image = new Messages();
        $form2 = $this->createForm(MessageFileType::class, $image);
        $form2->handleRequest($request);
        if ($form2->isSubmitted() && $form2->isValid()) {
            //
            $PictureFile = $form2->get('content')->getData();
            if ($PictureFile) {
                $originalFilename = pathinfo($PictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $security->getUser()->getFirstname()."_".$security->getUser()->getLastname().'-'.uniqid().'.'.$PictureFile->guessExtension();
                // Move the file to the directory
                try {
                    $PictureFile->move(
                        $this->getParameter('upload_directory_img_message'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    echo 'Impossible d\'enregistrer l\'Picture';
                }
                
                $image->setIdConversation($entityManager->getRepository(Conversation::class)->find($id));
                $image->setContent($newFilename);
            $image->setSender($entityManager->getRepository(User::class)->find($security->getUser()->getId()));
            $image->setRecipient($entityManager->getRepository(User::class)->find($idrecipiant));
            $image->setCreatedAt(new DateTimeImmutable());
            $image->setType('image');
            $entityManager->persist($image);
            $entityManager->flush();
            $notificationService->sendEmail(
                $entityManager->getRepository(User::class)->find($idrecipiant)->getEmail(),
                'Nouveau Message sur ClinUp',
                'email/nvMessage.html.twig',
                [
                    'user' => $entityManager->getRepository(User::class)->find($idrecipiant), // Objet ou tableau contenant les informations de l'utilisateur
                    
                ]
            );
            return $this->redirectToRoute('app_chat_show', ['id' => $id,'idrecipiant' => $idrecipiant,]);
            }
            
        }
        // Créez une nouvelle instance de Conversation
        $message = new Messages();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //dd($idConversation);
            $message->setIdConversation($entityManager->getRepository(Conversation::class)->find($id));
            $message->setSender($entityManager->getRepository(User::class)->find($security->getUser()->getId()));
            $message->setRecipient($entityManager->getRepository(User::class)->find($idrecipiant));
            $message->setCreatedAt(new DateTimeImmutable());
            $message->setType('text');
            $entityManager->persist($message);
            $entityManager->flush();
            return $this->redirectToRoute('app_chat_show', ['id' => $id,'idrecipiant' => $idrecipiant,]);
        }
        $idUser = $security->getUser()->getId();
        $qb = $entityManager->createQueryBuilder();
        $qb->select('c')
        ->from(Conversation::class, 'c')
        ->where($qb->expr()->orX(
            $qb->expr()->eq('c.participant1', ':id'),
            $qb->expr()->eq('c.participant2', ':id')
        ))
        ->setParameter('id', $idUser);
        $all[] = $qb->getQuery()->getResult();
        $conversations= $all[0];
        $conversationsTable = [];
        foreach($conversations as $conversation){
            $lastMessages = $entityManager
            ->getRepository(Messages::class)
            ->createQueryBuilder('d')
            ->where('d.idConversation = :id')
            ->setParameter('id', $conversation->getId())
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
            if ($conversation->getParticipant2()->getId() != $idUser){                
                if (!empty($lastMessages)) {
                    $lastMessage = $lastMessages[0];
                    $conversationsTable[] = [
                    'id' => $conversation->getId(),
                    'nom' => $conversation->getParticipant2()->getFirstname().' '.$conversation->getParticipant2()->getLastname(),
                    'picture' => $conversation->getParticipant2()->getPicture(),
                    'idRecipiant' => $conversation->getParticipant2()->getId(),
                    'lastMessage' => $lastMessage->getContent(),
                    'type' => $lastMessage->getType(),
                    'CreatedAt' => $lastMessage->getCreatedAt(),
                ];}
            }else{
                if (!empty($lastMessages)) {
                    $lastMessage = $lastMessages[0];
                    $conversationsTable[] = [
                    'id' => $conversation->getId(),
                    'nom' => $conversation->getParticipant1()->getFirstname().' '.$conversation->getParticipant1()->getLastname(),
                    'picture' => $conversation->getParticipant1()->getPicture(),
                    'idRecipiant' => $conversation->getParticipant1()->getId(),
                    'lastMessage' => $lastMessage->getContent(),
                    'type' => $lastMessage->getType(),
                    'CreatedAt' => $lastMessage->getCreatedAt(),
                ];}
            }
        }
        $messages = $entityManager->getRepository(Messages::class)->findBy(['idConversation' => $id]);
        //dd($messages);
        return $this->render('messengerPrest/messages.html.twig', [
            'messages' => $messages,
            'conversations' => $conversationsTable,
            'idUser' => $idUser,
            'form' => $form,
            'form2' => $form2,
            'idRecipiant' => $idrecipiant,
            "user" => $security->getUser(),
            'convUser' => $entityManager->getRepository(User::class)->find($idrecipiant),
        ]);
    }
    #[Route('{id}/conversation/create', name: 'app_conversation_create')]
    public function createConversation(int $id,ConversationRepository $conversationRepository,Security $security,EntityManagerInterface $em): Response
    {
        $userId = $security->getUser()->getId();
        $conversation = $em->getRepository(Conversation::class)->createQueryBuilder('c')
            ->where('c.participant1 = :user1Id')
            ->orWhere('c.participant1 = :user2Id')
            ->andWhere('c.participant2 = :user2Id')
            ->orWhere('c.participant2 = :user1Id')
            ->andWhere('c.participant1 != c.participant2')
            ->setParameter('user1Id', $userId)
            ->setParameter('user2Id', $id)
            ->getQuery()
            ->getOneOrNullResult();
        if ($conversation) {
            // La conversation existe déjà, redirigez l'utilisateur vers la conversation existante
            return $this->redirectToRoute('app_chat_show', ['id' => $conversation->getId(),'idrecipiant' => $id]);
        }else{
            $user1 = $em->getRepository(User::class)->find($userId);
            $user2 = $em->getRepository(User::class)->find($id);
            if (!$user1 || !$user2) {
                throw $this->createNotFoundException('Les utilisateurs spécifiés nexistent pas.');
            }
            // Créez une nouvelle instance de Conversation
            $newConversation = new Conversation();
            $newConversation->setParticipant1($user1);
            $newConversation->setParticipant2($user2);

            $em->persist($newConversation);
            $em->flush();
            return $this->redirectToRoute('app_chat_show', ['id' => $newConversation->getId(),'idrecipiant' => $id]);
        }
        return $this->render('conversation/index.html.twig', [
        ]);
    }
}
