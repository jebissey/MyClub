<?php

declare(strict_types=1);

namespace app\apis;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\LogDataWriterHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\CredentialService;

class WebmasterApi extends AbstractApi
{
    private const SERVICE = 'vapid';

    private array $vapid;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private LogDataWriterHelper $logDataWriterHelper,
        private CredentialService $credentials
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);

        $this->vapid = [
            'subject'    => Application::$root,
            'publicKey'  => $this->credentials->get(self::SERVICE, 'publicKey')  ?? '',
            'privateKey' => $this->credentials->get(self::SERVICE, 'privateKey') ?? '',
        ];
    }

    public function lastVersion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->logDataWriterHelper->add((string)ApplicationError::Ok->value, $_SERVER['HTTP_USER_AGENT'] ?? 'HTTP_USER_AGENT not defined');
        $this->renderJsonOk(['lastVersion' => Application::VERSION]);
    }

    public function sendNotification(): void
    {
        if (empty($this->vapid['publicKey']) || empty($this->vapid['privateKey'])) {
            $this->renderJson(
                ['message' => 'Les clés VAPID ne sont pas encore configurées.'],
                false,
                ApplicationError::InvalidSetting->value
            );
            return;
        }
        $request = $this->getJsonInput();
        $title = $request['title'] ?? 'Nouveau message';
        $body  = $request['body']  ?? 'Cliquez pour voir !';
        $webPush = new WebPush($this->vapid);
        $subscriptions = $this->dataHelper->gets('PushSubscription');
        $sent = 0;
        foreach ($subscriptions as $subData) {
            try {
                $subscription = Subscription::create([
                    'endpoint'  => $subData->EndPoint,
                    'publicKey' => $subData->P256dh,
                    'authToken' => $subData->Auth,
                ]);
                $payload = json_encode([
                    'title' => $title,
                    'body'  => $body,
                    'url'   => '/user/messages',
                ]);
                $report   = $webPush->sendOneNotification($subscription, $payload);
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

    public function subscribePush(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data     = $this->getJsonInput();
        $endpoint = trim($data['endpoint'] ?? '');
        $p256dh   = trim($data['p256dh']   ?? '');
        $auth     = trim($data['auth']     ?? '');
        $idPerson = $this->connectedUser?->person?->Id() ?? null;

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            $this->renderJsonBadRequest('Requête incomplète.', __FILE__, __LINE__);
            return;
        }
        if ($this->dataHelper->get('PushSubscription', ['EndPoint' => $endpoint])) {
            $this->renderJsonOk([], 'Déjà abonné.');
            return;
        }
        $this->dataHelper->set('PushSubscription', [
            'IdPerson' => $idPerson,
            'EndPoint' => $endpoint,
            'p256dh'   => $p256dh,
            'Auth'     => $auth,
        ]);
        $this->renderJsonOk([], 'Abonnement enregistré.');
    }
}