<?php

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\models\LogDataHelper;
use app\models\PageDataHelper;

class WebmasterApi extends AbstractApi
{
    private PageDataHelper $pageDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->pageDataHelper = new PageDataHelper($application);
    }

    public function addToGroup(int $personId, int $groupId): void
    {
        if (!(($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            if ($groupId === 1) {
                $this->renderJson(['error' => 'group 1 is locked'], false, ApplicationError::BadRequest->value);
                return;
            }
            if ($this->dataHelper->get('PersonGroup', ['IdPerson' => $personId, 'idGroup' => $groupId])) {
                $this->renderJson(['error' => "person ({$personId}) is already in group ({$groupId})"], false, ApplicationError::BadRequest->value);
                return;
            }
            $success = $this->dataHelper->set('PersonGroup', [['IdPerson' => $personId, 'idGroup' => $groupId]]) !== false;
            $this->renderJson([], $success, $success ? ApplicationError::Ok->value : ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }

    public function getPersonsInGroup(?int $id): void
    {
        if (!($this->connectedUser->get()->person ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
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
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }

    public function lastVersion(): void
    {
        if (!($this->connectedUser->get()->isWebmaster() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        (new LogDataHelper($this->application))->add(ApplicationError::Ok->value, $_SERVER['HTTP_USER_AGENT'] ?? 'HTTP_USER_AGENT not defined');
        $this->renderJson(['lastVersion' => Application::VERSION], true, ApplicationError::Ok->value);
    }

    public function removeFromGroup(int $personId, int $groupId): void
    {
        if (!(($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
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
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }

    #region Navbar
    public function deleteNavbarItem(int $id): void
    {
        if (!($this->connectedUser->get()->isNavbarDesigner() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $result = $this->pageDataHelper->del($id);
            $this->renderJson([], $result === 1, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }

    public function getNavbarItem(int $id): void
    {
        if (!($this->connectedUser->get()->isNavbarDesigner() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJson(['message' => 'Not allowed method: ' . $_SERVER['REQUEST_METHOD'] . ' in file ' . __FILE__ . ' at line ' . __LINE__], false, ApplicationError::MethodNotAllowed->value);
            return;
        }
        try {
            $this->renderJson(['message' => $this->pageDataHelper->get_($id)], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }

    public function saveNavbarItem(): void
    {
        if (!($this->connectedUser->get()->isNavbarDesigner() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name']) || empty($data['route'])) {
            $this->renderJson(['message' => 'Name and Route are required'], false, ApplicationError::Ok->value);
            return;
        }
        try {
            $this->pageDataHelper->insertOrUpdate($data);
            $this->renderJson([], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }

    public function updateNavbarPositions(): void
    {
        if (!($this->connectedUser->get()->isNavbarDesigner() ?? false)) {
            $this->renderUnauthorized(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $this->pageDataHelper->updates($data['positions']);
            $this->renderJson([], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e, ApplicationError::Error->value);
        }
    }
}
