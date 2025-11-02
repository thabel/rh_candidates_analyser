<?php

namespace App\Entity;

use App\Repository\CandidateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidateRepository::class)]
#[ORM\Table(name: 'candidates')]
class Candidate
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'text')]
    private ?string $cvText = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cvFileName = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $analysisResult = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $score = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $analyzedAt = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'pending'; // pending, analyzing, analyzed

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notificationSent = false;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'candidate', cascade: ['remove'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->id = $this->generateUuid();
        $this->createdAt = new \DateTimeImmutable();
        $this->submittedAt = new \DateTimeImmutable();
        $this->notifications = new ArrayCollection();
    }

    /**
     * Générer un UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return implode('-', [
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        ]);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getCvText(): ?string
    {
        return $this->cvText;
    }

    public function setCvText(string $cvText): static
    {
        $this->cvText = $cvText;
        return $this;
    }

    public function getCvFileName(): ?string
    {
        return $this->cvFileName;
    }

    public function setCvFileName(?string $cvFileName): static
    {
        $this->cvFileName = $cvFileName;
        return $this;
    }

    public function getAnalysisResult(): ?array
    {
        return $this->analysisResult;
    }

    public function setAnalysisResult(?array $analysisResult): static
    {
        $this->analysisResult = $analysisResult;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getAnalyzedAt(): ?\DateTimeImmutable
    {
        return $this->analyzedAt;
    }

    public function setAnalyzedAt(?\DateTimeImmutable $analyzedAt): static
    {
        $this->analyzedAt = $analyzedAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, ['pending', 'analyzing', 'analyzed'])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isNotificationSent(): bool
    {
        return $this->notificationSent;
    }

    public function setNotificationSent(bool $notificationSent): static
    {
        $this->notificationSent = $notificationSent;
        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setCandidate($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getCandidate() === $this) {
                $notification->setCandidate(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
