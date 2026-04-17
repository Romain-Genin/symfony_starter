<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'push_subscriptions')]
#[ORM\UniqueConstraint(columns: ['endpoint'])]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private string $endpoint;

    #[ORM\Column(length: 255)]
    private string $publicKey;

    #[ORM\Column(length: 255)]
    private string $authToken;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $endpoint, string $publicKey, string $authToken)
    {
        $this->endpoint   = $endpoint;
        $this->publicKey  = $publicKey;
        $this->authToken  = $authToken;
        $this->createdAt  = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEndpoint(): string { return $this->endpoint; }
    public function getPublicKey(): string { return $this->publicKey; }
    public function getAuthToken(): string { return $this->authToken; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
