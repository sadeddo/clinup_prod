<?php
namespace App\Service;

use App\Entity\Notif;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService {
    private $entityManager;
    private $logger;
    private $pushNotificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        PushNotificationService $pushNotificationService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->pushNotificationService = $pushNotificationService;
    }

    public function createNotification(User $user, string $content, ?string $targetUrl = null): void {
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

            // Envoi push
            $data = [];
            if ($targetUrl) {
                $data = ['screen' => 'WebviewScreen', 'params' => ['url' => $targetUrl]];
            }

            $this->pushNotificationService->sendNotificationToUser($user, '📬 Nouvelle notification', $content, $data);

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Erreur lors de la création ou de l\'envoi de la notification : %s', $e->getMessage()));
        }
    }
}
