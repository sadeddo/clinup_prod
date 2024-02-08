<?php

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EmailSender {
    private $mailer;
    private $twig;
    private $logger;

    public function __construct(MailerInterface $mailer, Environment $twig, LoggerInterface $logger) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function sendEmail($to, $subject, $template, $context) {
        $content = $this->twig->render($template, $context);

        $email = (new Email())
            ->from('oumaimasadeddine4@gmail.com')
            ->to($to)
            ->subject($subject)
            ->html($content);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // Log l'erreur pour le débogage
            $this->logger->error(sprintf('Erreur lors de l\'envoi de l\'email : %s', $e->getMessage()));
            // Considérez d'ajouter ici une action de secours si nécessaire
        }
    }
}
