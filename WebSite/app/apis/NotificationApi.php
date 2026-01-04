<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class NotificationApi extends AbstractApi
{
    public function __construct(Application $application, ConnectedUser $connectedUser, DataHelper $dataHelper, PersonDataHelper $personDataHelper)
    {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function registerPushSubscription(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $input = $this->getJsonInput();
        if (!isset($input['endpoint']) || !isset($input['auth']) || !isset($input['p256dh'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        $personId = $this->application->getConnectedUser()->person->Id;
        try {
            $existing = $this->dataHelper->get(
                'PushSubscription',
                ['EndPoint' => $input['endpoint']]
            );
            if ($existing) {
                $this->dataHelper->set(
                    'PushSubscription',
                    [
                        'IdPerson' => $personId,
                        'Auth' => $input['auth'],
                        'P256dh' => $input['p256dh']
                    ],
                    ['Id' => $existing->Id]
                );
            } else {
                $this->dataHelper->set(
                    'PushSubscription',
                    [
                        'IdPerson' => $personId,
                        'EndPoint' => $input['endpoint'],
                        'Auth' => $input['auth'],
                        'P256dh' => $input['p256dh']
                    ]
                );
            }
            $this->renderJsonCreated();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function deletePushSubscription(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $input = $this->getJsonInput();
        if (!isset($input['endpoint'])) {
            $this->renderJsonBadRequest('Endpoint manquant', __FILE__, __LINE__);
            return;
        }
        try {
            $existing = $this->dataHelper->get(
                'PushSubscription',
                ['EndPoint' => $input['endpoint']]
            );

            if ($existing) {
                $this->dataHelper->delete('PushSubscription', ['Id' => $existing->Id]);
            }
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
