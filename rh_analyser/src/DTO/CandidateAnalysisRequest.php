<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la requête d'analyse de candidature
 */
class CandidateAnalysisRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'La fiche de poste est obligatoire')]
        #[Assert\Length(
            min: 50,
            max: 10000,
            minMessage: 'La fiche de poste doit faire au moins 50 caractères',
            maxMessage: 'La fiche de poste ne doit pas dépasser 10000 caractères'
        )]
        private string $jobDescription = '',

        #[Assert\NotBlank(message: 'Le CV du candidat est obligatoire')]
        #[Assert\Length(
            min: 50,
            max: 10000,
            minMessage: 'Le CV doit faire au moins 50 caractères',
            maxMessage: 'Le CV ne doit pas dépasser 10000 caractères'
        )]
        private string $candidateCV = ''
    ) {}

    public function getJobDescription(): string
    {
        return $this->jobDescription;
    }

    public function setJobDescription(string $jobDescription): self
    {
        $this->jobDescription = $jobDescription;
        return $this;
    }

    public function getCandidateCV(): string
    {
        return $this->candidateCV;
    }

    public function setCandidateCV(string $candidateCV): self
    {
        $this->candidateCV = $candidateCV;
        return $this;
    }
}
