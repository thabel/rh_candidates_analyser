<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CandidateSubmissionRequest
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le prénom doit contenir au moins 2 caractères')]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins 2 caractères')]
    public string $lastName = '';

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le CV est obligatoire')]
    #[Assert\Length(min: 100, max: 50000, minMessage: 'Le CV doit contenir au moins 100 caractères', maxMessage: 'Le CV ne doit pas dépasser 50000 caractères')]
    public string $cvText = '';

    public ?string $cvFileName = null;

    public function __construct(
        string $firstName = '',
        string $lastName = '',
        string $email = '',
        string $cvText = '',
        ?string $cvFileName = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->cvText = $cvText;
        $this->cvFileName = $cvFileName;
    }
}
