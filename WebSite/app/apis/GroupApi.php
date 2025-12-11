<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class GroupApi extends AbstractApi
{
    public function __construct(Application $application, ConnectedUser $connectedUser, DataHelper $dataHelper, PersonDataHelper $personDataHelper)
    {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function addToGroup(int $personId, int $groupId): void
    {
        if (!(($this->application->getConnectedUser()->isGroupManager() ?? false))) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($groupId === 1) {
            $this->renderJsonBadRequest('group 1 is locked', __FILE__, __LINE__);
            return;
        }
        try {
            if (!$this->dataHelper->get('Person', ['Id' => $personId])) {
                $this->renderJsonBadRequest("person ({$personId}) does't exist)", __FILE__, __LINE__);
                return;
            }
            if (!$this->dataHelper->get('Group', ['Id' => $groupId])) {
                $this->renderJsonBadRequest("group ({$groupId}) does't exist)", __FILE__, __LINE__);
                return;
            }
            if ($this->dataHelper->get('PersonGroup', ['IdPerson' => $personId, 'idGroup' => $groupId])) {
                $this->renderJsonBadRequest("person ({$personId}) is already in group ({$groupId})", __FILE__, __LINE__);
                return;
            }
            $success = $this->dataHelper->set('PersonGroup', ['IdPerson' => $personId, 'IdGroup' => $groupId]) !== false;
            $this->renderJson([], $success, $success ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, __FILE__, __LINE__);
        }
    }

    public function getPersonsInGroup(?int $id): void
    {
        if (!($this->application->getConnectedUser()->person ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $this->renderJsonOk(['items' => $this->personDataHelper->getPersonsInGroup($id)]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, __FILE__, __LINE__);
        }
    }

    public function removeFromGroup(int $personId, int $groupId): void
    {
        if (!(($this->application->getConnectedUser()->isPersonManager() ?? false) || $this->application->getConnectedUser()->isWebmaster() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($groupId === 1) {
            $this->renderJsonBadRequest('group 1 is locked', __FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->dataHelper->delete('PersonGroup', ['IdPerson' => $personId, 'idGroup' => $groupId]) === 1;
            $this->renderJson([], $success, $success ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, __FILE__, __LINE__);
        }
    }
}
