<?php

namespace App\Service;

use App\Entity\User;
use App\Service\EmailSender;
use App\Entity\CommentPresta;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentPrestaRepository;

class RecommendationService
{
    private $commentPrestumRepository;
    private $entityManager;

    public function __construct(CommentPrestaRepository $commentPrestumRepository, EntityManagerInterface $entityManager,EmailSender $notificationService)
    {
        $this->commentPrestaRepository = $commentPrestumRepository;
        $this->entityManager = $entityManager;
    }

    public function handleRecommendation(CommentPresta $commentPrestum, EmailSender $notificationService): void
    {
        $prestataire = $commentPrestum->getPrestataire();
        $yesCount = $this->commentPrestaRepository->countYesRecommendations($prestataire->getId());
        $noCount = $this->commentPrestaRepository->countNoRecommendations($prestataire->getId());

        // Mise à jour du palier
        if ($yesCount >= 70) {
            $prestataire->setPalier('Or');
        } elseif ($yesCount >= 30) {
            $prestataire->setPalier('Argent');
        } elseif ($yesCount >= 10) {
            $prestataire->setPalier('Bronze');
        }

        // Désactiver le compte après 3 recommandations "non"
        if ($noCount >= 3) {
            $prestataire->setIsVerified(false);
            $notificationService->sendEmail(
                $prestataire->getEmail(),
                'Compte désactivé',
                'email/account_deactivated.html.twig',
                ['prestataire' => $prestataire]
            );

            $notificationService->sendEmail(
                'contact@clinup.fr',
                'Compte prestataire désactivé',
                'email/account_deactivated_admin.html.twig',
                ['prestataire' => $prestataire]
            );
        } elseif ($commentPrestum->getRecommandation() === 'Non') {
            $notificationService->sendEmail(
                'contact@clinup.fr',
                'Prestataire non recommandé',
                'email/non_recommended.html.twig',
                [
                    'prestataire' => $prestataire,
                    'commentPresta' => $commentPrestum,
                    'client' => $commentPrestum->getClient(),
                ]
            );
        }

        $this->entityManager->persist($prestataire);
        $this->entityManager->flush();
    }

    
}
