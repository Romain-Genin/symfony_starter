<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\EmailRequestFormType;
use App\Mailer\AppMailer;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/mot-de-passe-oublie')]
final class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $users, AppMailer $mailer): Response
    {
        $form = $this->createForm(EmailRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $users->findOneByEmail((string) $form->get('email')->getData());

            // Même réponse que le compte existe ou non : pas d'énumération des comptes.
            if (null !== $user) {
                try {
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);
                    $mailer->sendPasswordReset($user, $resetToken);
                    $this->setTokenObjectInSession($resetToken);
                } catch (ResetPasswordExceptionInterface) {
                    // Trop de demandes rapprochées : on ne révèle rien.
                }
            }

            return $this->redirectToRoute('app_check_email', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('reset_password/request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verifiez-vos-emails', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        // Jeton factice si aucun email n'a été envoyé : la page reste identique.
        $resetToken = $this->getTokenObjectFromSession() ?? $this->resetPasswordHelper->generateFakeResetToken();

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reinitialiser/{token?}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $hasher, ?string $token = null): Response
    {
        if ($token) {
            // Le jeton est retiré de l'URL (et donc de l'historique / du Referer).
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('Aucun jeton de réinitialisation trouvé.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            $this->addFlash('error', 'flash.reset_password.invalid_link');

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plainPassword));
            // Avoir reçu le lien par email prouve la possession de l'adresse.
            $user->setIsVerified(true);
            $this->em->flush();

            $this->cleanSessionAfterReset();
            $this->addFlash('success', 'flash.reset_password.changed');

            return $this->redirectToRoute('app_login', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('reset_password/reset.html.twig', [
            'form' => $form,
        ]);
    }
}
