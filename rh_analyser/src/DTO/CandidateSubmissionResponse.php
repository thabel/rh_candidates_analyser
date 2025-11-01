<?php

namespace App\DTO;

class CandidateSubmissionResponse
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $status,
        public string $message,
        public \DateTimeImmutable $submittedAt
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'status' => $this->status,
            'message' => $this->message,
            'submittedAt' => $this->submittedAt->format('Y-m-d H:i:s'),
        ];
    }
}
