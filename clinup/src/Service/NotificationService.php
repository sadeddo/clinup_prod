<?php

namespace App\Service;

use App\Entity\Notif;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService {
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createNotification($user, $content, $targetUrl) {
        try {
            date_default_timezone_set('Europe/Paris');
            $notif = new Notif();
            $notif->setUser($user);
            $notif->setContent($content);
            $notif->setIsRead(false);
            $notif->setTargetUrl($targetUrl);
            $notif->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($notif);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            $this->logger->error(sprintf('Erreur lors de la création de la notification : %s', $e->getMessage()));
            // Considérez d'ajouter ici une action de secours si nécessaire
        }
    }
}
