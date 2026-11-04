<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\EmailRequestFormType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Spam\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly RateLimiterFactoryInterface $accountActionsLimiter,
    ) {
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em, SpamChecker $spamChecker): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($spamChecker->isSpam($form)) {
                return $this->redirectToRoute('app_register_check_email', status: Response::HTTP_SEE_OTHER);
            }

            if (!$this->accountActionsLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $this->addFlash('error', 'Trop de tentatives. Merci de réessayer plus tard.');

                return $this->redirectToRoute('app_register', status: Response::HTTP_SEE_OTHER);
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plainPassword));

            $em->persist($user);
            $em->flush();

            $this->emailVerifier->sendConfirmation($user);

            return $this->redirectToRoute('app_register_check_email', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/inscription/verifiez-vos-emails', name: 'app_register_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('registration/check_email.html.twig');
    }

    #[Route('/inscription/confirmation', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request, UserRepository $users): Response
    {
        $user = $users->find($request->query->getInt('id'));

        if (null === $user) {
            $this->addFlash('error', 'Ce lien de confirmation n\'est pas valide.');

            return $this->redirectToRoute('app_register_resend', status: Response::HTTP_SEE_OTHER);
        }

        try {
            $this->emailVerifier->handleConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', 'Ce lien de confirmation est invalide ou a expiré. Vous pouvez en demander un nouveau.');

            return $this->redirectToRoute('app_register_resend', status: Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('success', 'Votre adresse email est confirmée. Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/inscription/renvoyer-confirmation', name: 'app_register_resend', methods: ['GET', 'POST'])]
    public function resend(Request $request, UserRepository $users): Response
    {
        $form = $this->createForm(EmailRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->accountActionsLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $user = $users->findOneByEmail((string) $form->get('email')->getData());

                if (null !== $user && !$user->isVerified()) {
                    $this->emailVerifier->sendConfirmation($user);
                }
            }

            // Réponse identique que le compte existe ou non (pas d'énumération des comptes).
            return $this->redirectToRoute('app_register_check_email', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('registration/resend.html.twig', [
            'form' => $form,
        ]);
    }
}
