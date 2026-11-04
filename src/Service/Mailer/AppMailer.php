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
    ) {}

    public function send(
        string|Address $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = [],
    ): void {
        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($to)
            ->subject($subject)
            ->html($this->twig->render($template, $context));

        foreach ($attachments as $a) {
            $email->attachFromPath($a['path'], $a['name'] ?? null, $a['mime'] ?? null);
        }

        $this->mailer->send($email);
    }
}
