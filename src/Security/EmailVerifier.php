<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Mailer\AppMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final readonly class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private AppMailer $mailer,
        private EntityManagerInterface $em,
    ) {
    }

    public function sendConfirmation(User $user): void
    {
        $signature = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId()],
        );

        $this->mailer->sendEmailVerification($user, $signature->getSignedUrl(), $signature->getExpiresAt());
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());

        $user->setIsVerified(true);
        $this->em->flush();
    }
}
