<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\DeleteAccountFormType;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('', name: 'app_account', methods: ['GET', 'POST'])]
    public function index(Request $request, #[CurrentUser] User $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'flash.account.updated');

            return $this->redirectToRoute('app_account', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function password(Request $request, #[CurrentUser] User $user, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ChangePasswordFormType::class, null, ['require_current_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plainPassword));
            $em->flush();

            $this->addFlash('success', 'flash.account.password_changed');

            return $this->redirectToRoute('app_account', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Droit à l'effacement (RGPD, article 17).
     */
    #[Route('/supprimer', name: 'app_account_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, #[CurrentUser] User $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DeleteAccountFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($user);
            $em->flush();

            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $this->addFlash('success', 'flash.account.deleted');

            return $this->redirectToRoute('app_home', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/delete.html.twig', [
            'form' => $form,
        ]);
    }
}
