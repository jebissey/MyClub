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
        parent::__construct($application, $connectedUser,$dataHelper, $personDataHelper);
    }

    public function addToGroup(int $personId, int $groupId): void
    {
        if (!(($this->application->getConnectedUser()->get()->isGroupManager() ?? false))) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($groupId === 1) {
            $this->renderJson(['error' => 'group 1 is locked'], false, ApplicationError::BadRequest->value);
            return;
        }
        try {
            if (!$this->dataHelper->get('Person', ['Id' => $personId])) {
                $this->renderJson(['error' => "person ({$personId}) does't exist)"], false, ApplicationError::BadRequest->value);
                return;
            }
            if (!$this->dataHelper->get('Group', ['Id' => $groupId])) {
                $this->renderJson(['error' => "group ({$groupId}) does't exist)"], false, ApplicationError::BadRequest->value);
                return;
            }
            if ($this->dataHelper->get('PersonGroup', ['IdPerson' => $personId, 'idGroup' => $groupId])) {
                $this->renderJson(['error' => "person ({$personId}) is already in group ({$groupId})"], false, ApplicationError::BadRequest->value);
                return;
            }
            $success = $this->dataHelper->set('PersonGroup', ['IdPerson' => $personId, 'IdGroup' => $groupId]) !== false;
            $this->renderJson([], $success, $success ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getPersonsInGroup(?int $id): void
    {
        if (!($this->application->getConnectedUser()->get()->person ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $users = $this->personDataHelper->getPersonsInGroup($id);
            $this->renderJson($users, true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function removeFromGroup(int $personId, int $groupId): void
    {
        if (!(($this->application->getConnectedUser()->get()->isPersonManager() ?? false) || $this->application->getConnectedUser()->isWebmaster() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($groupId === 1) {
            $this->renderJson(['error' => 'group 1 is locked'], false, ApplicationError::BadRequest->value);
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
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }
}
