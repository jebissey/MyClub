<?php

declare(strict_types=1);

namespace app\helpers;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Throwable;

class NotificationSender
{
    private $dataHelper;
    private $vapidPublicKey;
    private $vapidPrivateKey;

    public function __construct($dataHelper)
    {
        $this->dataHelper = $dataHelper;
        $this->loadVapidKeys();
    }

    private function loadVapidKeys(): void
    {
        $metadata = $this->dataHelper->get('Metadata', ['Id' => 1], 'VapidPublicKey, VapidPrivateKey');
        $this->vapidPublicKey = $metadata->VapidPublicKey ?? null;
        $this->vapidPrivateKey = $metadata->VapidPrivateKey ?? null;
    }

    public function sendToRecipients(array $recipients, array $notificationData): void
    {
        error_log("\n\n" . json_encode($recipients, JSON_PRETTY_PRINT) . "\n");
        error_log("\n\n" . json_encode($notificationData, JSON_PRETTY_PRINT) . "\n");
        if (!$this->vapidPublicKey || !$this->vapidPrivateKey) {
            error_log('VAPID keys not configured');
            return;
        }
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@example.com', // Changez ceci
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ]
        ];
        $webPush = new WebPush($auth);

        foreach ($recipients as $recipient) {
            try {
                $subscriptionData = $this->dataHelper->gets('PushSubscription', ['IdPerson' => $recipient]);
                if (empty($subscriptionData)) {
                    error_log("No push subscription found for user ID: {$recipient}");
                    continue;
                }
                foreach ($subscriptionData as $sub) {
                    $subscription = Subscription::create([
                        'endpoint' => $sub['EndPoint'],
                        'keys' => [
                            'auth' => $sub['Auth'],
                            'p256dh' => $sub['P256dh'] ?? '',
                        ],
                    ]);
                    $webPush->queueNotification(
                        $subscription,
                        json_encode($notificationData)
                    );
                }
            } catch (Throwable $e) {
                error_log("Error creating subscription: " . $e->getMessage());
            }
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if (!$report->isSuccess()) {
                error_log("Notification failed for {$endpoint}: " . $report->getReason());

                // Si l'abonnement est expiré ou invalide, le supprimer
                if ($report->isSubscriptionExpired()) {
                    $this->dataHelper->delete('PushSubscription', ['EndPoint' => $endpoint]);
                }
            }
        }
    }
}
