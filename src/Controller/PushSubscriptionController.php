<?php

namespace App\Controller;

use App\Entity\PushSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/push')]
class PushSubscriptionController extends AbstractController
{
    #[Route('/subscribe', name: 'push_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
            return $this->json(['error' => 'Invalid payload'], 400);
        }

        $repo = $em->getRepository(PushSubscription::class);
        $existing = $repo->findOneBy(['endpoint' => $data['endpoint']]);

        if (!$existing) {
            $sub = new PushSubscription(
                endpoint:   $data['endpoint'],
                publicKey:  $data['keys']['p256dh'],
                authToken:  $data['keys']['auth'],
            );
            $em->persist($sub);
            $em->flush();
        }

        return $this->json(['status' => 'ok'], 201);
    }

    #[Route('/unsubscribe', name: 'push_unsubscribe', methods: ['DELETE'])]
    public function unsubscribe(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['endpoint'])) {
            return $this->json(['error' => 'Missing endpoint'], 400);
        }

        $repo = $em->getRepository(PushSubscription::class);
        $sub  = $repo->findOneBy(['endpoint' => $data['endpoint']]);

        if ($sub) {
            $em->remove($sub);
            $em->flush();
        }

        return $this->json(['status' => 'ok']);
    }
}
