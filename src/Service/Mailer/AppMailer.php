<?php

namespace App\Service\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AppMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $senderEmail,
        private readonly string $senderName,
    ) {
    }

    public function send(
        string|Address $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = [],
    ): void {
        $html = $this->twig->render($template, $context);

        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($to)
            ->subject($subject)
            ->html($html);

        foreach ($attachments as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['name'] ?? null, $attachment['mime'] ?? null);
        }

        $this->mailer->send($email);
    }
}
