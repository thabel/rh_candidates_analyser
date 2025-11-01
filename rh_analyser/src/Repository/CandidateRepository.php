<?php

namespace App\Repository;

use App\Entity\Candidate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Candidate>
 */
class CandidateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidate::class);
    }

    /**
     * Récupère toutes les candidatures avec tri par date décroissante
     */
    public function findAllWithLatest(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les candidatures en attente d'analyse
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('c.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les candidatures analysées
     */
    public function findAnalyzed(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', 'analyzed')
            ->orderBy('c.analyzedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cherche par email
     */
    public function findByEmail(string $email): ?Candidate
    {
        return $this->findOneBy(['email' => $email]);
    }
}
