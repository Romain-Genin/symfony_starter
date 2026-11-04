<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Entity\ContactMessage;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

/**
 * Point d'entrée unique pour tous les emails transactionnels de l'application.
 * Templates : templates/emails/*.html.twig (Inky + CSS inliné).
 * L'expéditeur est défini globalement (MAILER_FROM, config/packages/mailer.yaml).
 * Transport : Mailpit en local, Brevo (brevo+api://) en production.
 */
final readonly class AppMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $contactRecipient,
    ) {
    }

    public function sendContactNotification(ContactMessage $message): void
    {
        $this->send(
            new TemplatedEmail()
                ->to($this->contactRecipient)
                ->replyTo(new Address((string) $message->getEmail(), (string) $message->getName()))
                ->subject(\sprintf('[Contact] %s', $message->getSubject()))
                ->htmlTemplate('emails/contact_notification.html.twig')
                ->context(['message' => $message]),
            'contact-notification',
        );
    }

    public function sendContactAcknowledgement(ContactMessage $message): void
    {
        $this->send(
            new TemplatedEmail()
                ->to(new Address((string) $message->getEmail(), (string) $message->getName()))
                ->subject('Nous avons bien reçu votre message')
                ->htmlTemplate('emails/contact_acknowledgement.html.twig')
                ->context(['message' => $message]),
            'contact-acknowledgement',
        );
    }

    public function sendEmailVerification(User $user, string $signedUrl, \DateTimeInterface $expiresAt): void
    {
        $this->send(
            new TemplatedEmail()
                ->to(new Address((string) $user->getEmail(), $user->getFullName()))
                ->subject('Confirmez votre adresse email')
                ->htmlTemplate('emails/verify_email.html.twig')
                ->context(['user' => $user, 'signedUrl' => $signedUrl, 'expiresAt' => $expiresAt]),
            'verify-email',
        );
    }

    public function sendPasswordReset(User $user, ResetPasswordToken $token): void
    {
        $this->send(
            new TemplatedEmail()
                ->to(new Address((string) $user->getEmail(), $user->getFullName()))
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context(['user' => $user, 'resetToken' => $token]),
            'reset-password',
        );
    }

    /**
     * Le tag est transmis à Brevo (statistiques par type d'email dans le dashboard Brevo).
     */
    private function send(TemplatedEmail $email, string $tag): void
    {
        $email->getHeaders()->add(new TagHeader($tag));
        $this->mailer->send($email);
    }
}
