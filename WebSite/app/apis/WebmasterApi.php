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
                $this->renderJson(['success' => $this->personGroupDataHelper->add($personId, $groupId)]);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }

    public function getPersonsInGroup(?int $id): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                try {
                    $users = $this->personDataHelper->getPersonsInGroup($id);
                    $this->renderJson($users);
                } catch (Throwable $e) {
                    $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
                }
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], ApplicationError::MethodNotAllowed->value);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }

    public function lastVersion()
    {
        (new LogDataHelper($this->application))->add(200, $_SERVER['HTTP_USER_AGENT'] ?? 'HTTP_USER_AGENT not defined');
        $this->renderJson(['lastVersion' => Application::VERSION]);
    }

    public function removeFromGroup($personId, $groupId)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            try {
                $this->renderJson(['success' => $this->personGroupDataHelper->del($personId, $groupId)]);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }

    #region Navbar
    public function deleteNavbarItem($id)
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            try {
                $result = $this->pageDataHelper->del($id);
                $this->renderJson(['success' => $result == 1]);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }

    public function getNavbarItem($id)
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            try {
                $this->renderJson(['success' => true, 'message' => $this->pageDataHelper->get_($id)]);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }

    public function saveNavbarItem()
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['route'])) {
                $this->renderJson(['success' => false, 'message' => 'Name and Route are required']);
                return;
            }
            try {
                $this->pageDataHelper->insertOrUpdate($data);
                $this->renderJson(['success' => true]);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }

    public function updateNavbarPositions()
    {
        if ($this->connectedUser->isWebmaster() ?? false) {
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $this->pageDataHelper->updates($data['positions']);
                $this->renderJson(['success' => true]);
            } catch (Throwable $e) {
                $this->renderJson(['error' => $e->getMessage()], ApplicationError::Error->value);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value);
    }
}
