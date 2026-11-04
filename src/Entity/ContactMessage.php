<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\Type\EncryptedType;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message envoyé via le formulaire de contact. Toutes les données personnelles sont chiffrées.
 */
#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Index(name: 'idx_contact_message_created_at', columns: ['created_at'])]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: EncryptedType::NAME)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer votre nom.')]
    #[Assert\Length(max: 120)]
    private ?string $name = null;

    #[ORM\Column(type: EncryptedType::NAME)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer votre adresse email.')]
    #[Assert\Email(message: 'Cette adresse email n\'est pas valide.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(type: EncryptedType::NAME, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(pattern: '/^[0-9+().\s-]*$/', message: 'Ce numéro de téléphone n\'est pas valide.')]
    private ?string $phone = null;

    #[ORM\Column(type: EncryptedType::NAME)]
    #[Assert\NotBlank(message: 'Merci d\'indiquer l\'objet de votre message.')]
    #[Assert\Length(max: 150)]
    private ?string $subject = null;

    #[ORM\Column(type: EncryptedType::NAME)]
    #[Assert\NotBlank(message: 'Merci d\'écrire votre message.')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'Votre message doit contenir au moins {{ limit }} caractères.')]
    private ?string $message = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return \sprintf('%s — %s', $this->name, $this->subject);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = null === $email ? null : mb_strtolower(trim($email));

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function isRead(): bool
    {
        return null !== $this->readAt;
    }

    public function markAsRead(): static
    {
        $this->readAt ??= new \DateTimeImmutable();

        return $this;
    }

    public function markAsUnread(): static
    {
        $this->readAt = null;

        return $this;
    }
}
