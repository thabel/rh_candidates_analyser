<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findByCandidate($candidateId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.candidate = :candidateId')
            ->setParameter('candidateId', $candidateId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnreadByCandidate($candidateId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.candidate = :candidateId')
            ->andWhere('n.isRead = false')
            ->setParameter('candidateId', $candidateId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
