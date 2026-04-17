<?php

namespace App\Service\Push;

use App\Entity\PushSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct(
        private readonly EntityManagerInterface $em,
        string $vapidPublicKey,
        string $vapidPrivateKey,
        string $vapidSubject,
    ) {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => $vapidSubject,
                'publicKey'  => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ],
        ]);
    }

    /**
     * Envoie une notification à toutes les subscriptions enregistrées.
     *
     * @param array{ title: string, body: string, url?: string, icon?: string, actions?: array } $payload
     */
    public function sendToAll(array $payload): void
    {
        $subscriptions = $this->em->getRepository(PushSubscription::class)->findAll();

        foreach ($subscriptions as $sub) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->getEndpoint(),
                    'keys'            => [
                        'p256dh' => $sub->getPublicKey(),
                        'auth'   => $sub->getAuthToken(),
                    ],
                ]),
                json_encode($payload, JSON_THROW_ON_ERROR)
            );
        }

        $this->flush();
    }

    /**
     * Envoie une notification à une subscription spécifique.
     *
     * @param array{ title: string, body: string, url?: string, icon?: string } $payload
     */
    public function sendTo(PushSubscription $sub, array $payload): void
    {
        $this->webPush->queueNotification(
            Subscription::create([
                'endpoint' => $sub->getEndpoint(),
                'keys'     => [
                    'p256dh' => $sub->getPublicKey(),
                    'auth'   => $sub->getAuthToken(),
                ],
            ]),
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->flush();
    }

    private function flush(): void
    {
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                // Subscription expirée — on la supprime
                if ($report->isSubscriptionExpired()) {
                    $sub = $this->em->getRepository(PushSubscription::class)
                        ->findOneBy(['endpoint' => $report->getEndpoint()]);
                    if ($sub) {
                        $this->em->remove($sub);
                    }
                }
            }
        }
        $this->em->flush();
    }
}
