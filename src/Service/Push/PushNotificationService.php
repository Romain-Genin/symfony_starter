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

    public function sendToAll(array $payload): void
    {
        foreach ($this->em->getRepository(PushSubscription::class)->findAll() as $sub) {
            $this->queue($sub, $payload);
        }
        $this->flush();
    }

    public function sendTo(PushSubscription $sub, array $payload): void
    {
        $this->queue($sub, $payload);
        $this->flush();
    }

    private function queue(PushSubscription $sub, array $payload): void
    {
        $this->webPush->queueNotification(
            Subscription::create(['endpoint' => $sub->getEndpoint(), 'keys' => ['p256dh' => $sub->getPublicKey(), 'auth' => $sub->getAuthToken()]]),
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    private function flush(): void
    {
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                $sub = $this->em->getRepository(PushSubscription::class)->findOneBy(['endpoint' => $report->getEndpoint()]);
                if ($sub) { $this->em->remove($sub); }
            }
        }
        $this->em->flush();
    }
}
