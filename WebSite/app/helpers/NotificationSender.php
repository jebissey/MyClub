<?php

declare(strict_types=1);

namespace app\helpers;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Throwable;

use app\models\DataHelper;
use app\Modules\Common\services\CredentialService;

class NotificationSender
{
    private const SERVICE = 'vapid';

    private ?string $vapidPublicKey;
    private ?string $vapidPrivateKey;

    public function __construct(
        private DataHelper $dataHelper,
        CredentialService $credentials
    ) {
        $this->vapidPublicKey  = $credentials->get(self::SERVICE, 'publicKey');
        $this->vapidPrivateKey = $credentials->get(self::SERVICE, 'privateKey');
    }

    public function sendToRecipients(array $recipients, array $notificationData, ?string $excludeEndpoint = null): void
    {
        if (!$this->vapidPublicKey || !$this->vapidPrivateKey) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => 'mailto:admin@example.com',
                'publicKey'  => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ]);

        foreach ($recipients as $recipient) {
            try {
                $conditions = ['IdPerson' => $recipient];
                if ($excludeEndpoint !== null) {
                    $conditions['EndPoint !='] = $excludeEndpoint;
                }
                $subscriptionData = $this->dataHelper->gets('PushSubscription', $conditions);
                if (empty($subscriptionData)) {
                    continue;
                }
                foreach ($subscriptionData as $sub) {
                    $subscription = Subscription::create([
                        'endpoint' => $sub->EndPoint,
                        'keys'     => [
                            'auth'   => $sub->Auth,
                            'p256dh' => $sub->P256dh ?? '',
                        ],
                    ]);
                    $webPush->queueNotification($subscription, json_encode($notificationData));
                }
            } catch (Throwable $e) {
error_log("Error creating subscription: " . $e->getMessage());
            }
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if (!$report->isSuccess()) {
error_log("Notification failed for {$endpoint}: " . $report->getReason());
                if ($report->isSubscriptionExpired()) {
                    $this->dataHelper->delete('PushSubscription', ['EndPoint' => $endpoint]);
                }
            }
        }
    }
}