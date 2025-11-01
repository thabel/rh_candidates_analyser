<?php

namespace App\Repository;

use App\Entity\JobDescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobDescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobDescription::class);
    }

    public function findActiveJob(): ?JobDescription
    {
        return $this->findOneBy(['isActive' => true]);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }
}
