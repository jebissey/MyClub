<?php

declare(strict_types=1);

namespace app\apis;

use stdClass;
use Throwable;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;
use app\modules\Notifications\valueObjects\PushSubscriptionRow;

final class NotificationApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function registerPushSubscription(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $input = $this->getJsonInput();
        if (!isset($input['endpoint']) || !isset($input['auth']) || !isset($input['p256dh'])) {
            $this->renderJsonBadRequest('Données manquantes', __FILE__, __LINE__);
            return;
        }
        $personId = $this->application->getConnectedUser(true)->person->id;
        try {
            $existingId = $this->findSubscriptionId($input['endpoint']);
            if ($existingId !== null) {
                $this->dataHelper->set(
                    'PushSubscription',
                    [
                        'IdPerson' => $personId,
                        'Auth' => $input['auth'],
                        'P256dh' => $input['p256dh']
                    ],
                    ['Id' => $existingId]
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
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function deletePushSubscription(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $input = $this->getJsonInput();
        if (!isset($input['endpoint'])) {
            $this->renderJsonBadRequest('Endpoint manquant', __FILE__, __LINE__);
            return;
        }
        try {
            $existingId = $this->findSubscriptionId($input['endpoint']);
            if ($existingId !== null) {
                $this->dataHelper->delete('PushSubscription', ['Id' => $existingId]);
            }
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * Id of the push subscription registered for the given endpoint, or null when there is none.
     */
    private function findSubscriptionId(mixed $endpoint): ?int
    {
        $row = $this->dataHelper->get('PushSubscription', ['EndPoint' => $endpoint], 'Id');
        if ($row === false) {
            return null;
        }
        /** @var object{Id: int|string} $row */
        return (int) $row->Id;
    }
}
