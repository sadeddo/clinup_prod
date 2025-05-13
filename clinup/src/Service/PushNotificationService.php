<?php
namespace App\Service;

use App\Entity\DeviceToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class PushNotificationService
{
    private $entityManager;
    private $logger;
    private $expoPushEndpoint = 'https://exp.host/--/api/v2/push/send';

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function sendNotificationToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $this->entityManager->getRepository(DeviceToken::class)->findBy(['user' => $user]);
        
        foreach ($tokens as $token) {
            $this->sendNotification($token->getToken(), $title, $body, $data);
        }
    }

    public function sendNotificationToAllUsers(string $title, string $body, array $data = []): void
    {
        $tokens = $this->entityManager->getRepository(DeviceToken::class)->findAll();
        
        foreach ($tokens as $token) {
            $this->sendNotification($token->getToken(), $title, $body, $data);
        }
    }

    private function sendNotification(string $token, string $title, string $body, array $data = []): void
{
    try {
        $client = HttpClient::create();

        $message = [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'badge' => 1,
        ];

        $response = $client->request('POST', $this->expoPushEndpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ],
            'json' => $message,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->toArray(false); // ne throw pas exception automatique

        // ✅ Réponse bien structurée ?
        if (!isset($content['data']) || !isset($content['data']['status']) || $content['data']['status'] !== 'ok') {
            $this->logger->error('❌ Notification non envoyée', [
                'statusCode' => $statusCode,
                'response' => $content,
                'token' => $token,
            ]);
        } else {
            $this->logger->info('✅ Notification envoyée', [
                'token' => $token,
                'response' => $content,
            ]);
        }
    } catch (\Exception $e) {
        $this->logger->error('🚨 Exception lors de l\'envoi de notification', [
            'message' => $e->getMessage(),
            'token' => $token,
        ]);
    }
}

}