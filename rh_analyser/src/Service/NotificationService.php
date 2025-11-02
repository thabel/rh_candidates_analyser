<?php

namespace App\Service;

use App\Entity\Candidate;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private const SCORE_THRESHOLD = 80;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Envoyer une notification si le score >= seuil
     */
    public function notifyIfQualified(Candidate $candidate, int $score): void
    {
        if ($score >= self::SCORE_THRESHOLD && !$candidate->isNotificationSent()) {
            $notification = new Notification();
            $notification->setCandidate($candidate);
            $notification->setTitle('🎉 Bonne nouvelle!');
            $notification->setMessage(
                "Félicitations! Votre candidature a obtenu un score de {$score}/100, "
                . "ce qui dépasse notre seuil minimum de " . self::SCORE_THRESHOLD . ". "
                . "Nous serions intéressés par un entretien avec vous!"
            );
            $notification->setScore($score);

            $this->entityManager->persist($notification);
            $candidate->setNotificationSent(true);
            $this->entityManager->flush();

            $this->logger->info('Notification envoyée', [
                'candidateId' => $candidate->getId(),
                'score' => $score
            ]);
        }
    }

    /**
     * Envoyer une notification de rejet
     */
    public function notifyIfRejected(Candidate $candidate, int $score): void
    {
        if ($score < self::SCORE_THRESHOLD && !$candidate->isNotificationSent()) {
            $notification = new Notification();
            $notification->setCandidate($candidate);
            $notification->setTitle('📋 Résultat de votre candidature');
            $notification->setMessage(
                "Merci de votre intérêt! Votre candidature a obtenu un score de {$score}/100. "
                . "Malheureusement, ce score est inférieur à notre seuil de {" . self::SCORE_THRESHOLD . "/100. "
                . "Nous vous encourageons à postuler à nouveau dans le futur!"
            );
            $notification->setScore($score);

            $this->entityManager->persist($notification);
            $candidate->setNotificationSent(true);
            $this->entityManager->flush();

            $this->logger->info('Notification de rejet envoyée', [
                'candidateId' => $candidate->getId(),
                'score' => $score
            ]);
        }
    }

    /**
     * Obtenir le seuil
     */
    public function getThreshold(): int
    {
        return self::SCORE_THRESHOLD;
    }
}
