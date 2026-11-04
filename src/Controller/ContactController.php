<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactType;
use App\Mailer\AppMailer;
use App\Spam\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'], options: ['sitemap' => ['priority' => 0.8]])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        AppMailer $mailer,
        SpamChecker $spamChecker,
        RateLimiterFactoryInterface $contactFormLimiter,
        LoggerInterface $logger,
    ): Response {
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactType::class, $contactMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Robot détecté : on simule un succès pour ne pas lui donner d'indice.
            if ($spamChecker->isSpam($form)) {
                $logger->notice('Contact form: spam blocked', ['ip' => $request->getClientIp()]);

                return $this->success();
            }

            if (!$contactFormLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $this->addFlash('error', 'Vous avez envoyé plusieurs messages en peu de temps. Merci de réessayer dans quelques minutes.');

                return $this->redirectToRoute('app_contact', status: Response::HTTP_SEE_OTHER);
            }

            $em->persist($contactMessage);
            $em->flush();

            try {
                $mailer->sendContactNotification($contactMessage);
                $mailer->sendContactAcknowledgement($contactMessage);
            } catch (TransportExceptionInterface $e) {
                // Le message est enregistré en base (visible dans l'admin) : on ne bloque pas l'utilisateur.
                $logger->error('Contact form: email sending failed', ['exception' => $e, 'message_id' => $contactMessage->getId()]);
            }

            return $this->success();
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }

    private function success(): Response
    {
        $this->addFlash('success', 'Merci, votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.');

        return $this->redirectToRoute('app_contact', status: Response::HTTP_SEE_OTHER);
    }
}
