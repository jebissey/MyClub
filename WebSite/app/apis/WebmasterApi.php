<?php

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\models\LogDataHelper;
use app\models\PageDataHelper;
use app\models\PersonGroupDataHelper;

class WebmasterApi extends AbstractApi
{
    private PageDataHelper $pageDataHelper;
    private PersonGroupDataHelper $personGroupDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->pageDataHelper = new PageDataHelper($application);
        $this->personGroupDataHelper = new PersonGroupDataHelper($application);
    }

    public function addToGroup($personId, $groupId)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            try {
                $this->renderJson([], $this->personGroupDataHelper->add($personId, $groupId), ApplicationError::Ok->value);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], false, ApplicationError::Error->value);
            }
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }

    public function getPersonsInGroup(?int $id): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                try {
                    $users = $this->personDataHelper->getPersonsInGroup($id);
                    $this->renderJson($users, true, ApplicationError::Ok->value);
                } catch (Throwable $e) {
                    $this->renderJsonError($e, ApplicationError::Error->value);
                }
            } else $this->renderJson(['message' => 'Bad request method'], false, ApplicationError::MethodNotAllowed->value);
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }

    public function lastVersion()
    {
        (new LogDataHelper($this->application))->add(ApplicationError::Ok->value, $_SERVER['HTTP_USER_AGENT'] ?? 'HTTP_USER_AGENT not defined');
        $this->renderJson(['lastVersion' => Application::VERSION], true, ApplicationError::Ok->value);
    }

    public function removeFromGroup($personId, $groupId)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            try {
                $this->renderJson([], $this->personGroupDataHelper->del($personId, $groupId) === 1, ApplicationError::Ok->value);
            } catch (Throwable $e) {
                $this->renderJsonError($e, ApplicationError::Error->value);
            }
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }

    #region Navbar
    public function deleteNavbarItem($id)
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            try {
                $result = $this->pageDataHelper->del($id);
                $this->renderJson([], $result === 1, ApplicationError::Ok->value);
            } catch (Throwable $e) {
                $this->renderJsonError($e, ApplicationError::Error->value);
            }
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }

    public function getNavbarItem($id)
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            try {
                $this->renderJson(['message' => $this->pageDataHelper->get_($id)], true, ApplicationError::Ok->value);
            } catch (Throwable $e) {
                $this->renderJsonError($e, ApplicationError::Error->value);
            }
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }

    public function saveNavbarItem()
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
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
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }

    public function updateNavbarPositions()
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $this->pageDataHelper->updates($data['positions']);
                $this->renderJson([], true, ApplicationError::Ok->value);
            } catch (Throwable $e) {
                $this->renderJsonError($e, ApplicationError::Error->value);
            }
        } else $this->renderJson(['message' => 'User not allowed'], false, ApplicationError::Forbidden->value);
    }
}
