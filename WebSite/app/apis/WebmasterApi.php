<?php

declare(strict_types=1);

namespace app\apis;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\LanguagesDataHelper;
use app\models\LogWriterDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\CredentialService;

final class WebmasterApi extends AbstractApi
{
    private const SERVICE = 'vapid';

    /** @var array{subject: string, publicKey: string, privateKey: string} */
    private readonly array $vapid;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly LogWriterDataHelper $logWriterDataHelper,
        private readonly CredentialService $credentials
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);

        $this->vapid = [
            'subject'    => Application::$root,
            'publicKey'  => $this->credentials->get(self::SERVICE, 'publicKey')  ?? '',
            'privateKey' => $this->credentials->get(self::SERVICE, 'privateKey') ?? '',
        ];
    }

    public function lastVersion(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $userAgentRaw = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $userAgent = is_string($userAgentRaw) ? $userAgentRaw : 'HTTP_USER_AGENT not defined';
        $this->logWriterDataHelper->add((string)ApplicationError::Ok->value, $userAgent);
        $this->renderJsonOk(['lastVersion' => Application::VERSION]);
    }

    public function sendNotification(): void
    {
        // Only webmasters may broadcast a push notification, and only with POST.
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            return;
        }
        if (empty($this->vapid['publicKey']) || empty($this->vapid['privateKey'])) {
            $this->renderJson(
                ['message' => $this->languagesDataHelper->translate('webmaster.notification.vapid_not_configured')],
                false,
                ApplicationError::ServiceUnavailable->value
            );
            return;
        }
        $request = $this->getJsonInput();
        $title = $request['title'] ?? $this->languagesDataHelper->translate('webmaster.notification.default_title');
        $body  = $request['body']  ?? $this->languagesDataHelper->translate('webmaster.notification.default_body');

        $webPush = new WebPush($this->vapid);
        $subscriptions = $this->dataHelper->gets('PushSubscription', [], 'EndPoint, P256dh, Auth');
        $sent = 0;
        foreach ($subscriptions as $subData) {
            try {
                $subscription = Subscription::create([
                    'endpoint'  => $subData->EndPoint,
                    'publicKey' => $subData->P256dh,
                    'authToken' => $subData->Auth,
                ]);
                $encoded = json_encode([
                    'title' => $title,
                    'body'  => $body,
                    'url'   => '/user/messages',
                ]);
                $payload = $encoded !== false ? $encoded : null;
                $report  = $webPush->sendOneNotification($subscription, $payload);
                $response = $report->getResponse();
                if ($response && $response->getStatusCode() === ApplicationError::Gone->value) {
                    $this->dataHelper->delete('PushSubscription', ['EndPoint' => $subData->EndPoint]);
                } else {
                    $sent++;
                }
            } catch (Throwable $e) {
                $this->application->getErrorManager()->raise(
                    ApplicationError::Error,
                    "Push error {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}"
                );
            }
        }
        $this->renderJsonOk(['sent' => $sent]);
    }
}
