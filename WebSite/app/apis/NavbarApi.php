<?php

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\PageDataHelper;
use app\models\PersonDataHelper;

class NavbarApi extends AbstractApi
{
    public function __construct(Application $application, private PageDataHelper $pageDataHelper, ConnectedUser $connectedUser, DataHelper $dataHelper, PersonDataHelper $personDataHelper)
    {
        parent::__construct($application, $connectedUser,$dataHelper, $personDataHelper);
    }

    public function deleteNavbarItem(int $id): void
    {
        if (!($this->application->getConnectedUser()->get()->isNavbarDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $result = $this->pageDataHelper->del($id);
            $this->renderJson([], $result === 1, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getNavbarItem(int $id): void
    {
        if (!($this->application->getConnectedUser()->get()->isNavbarDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJson(['message' => 'Not allowed method: ' . $_SERVER['REQUEST_METHOD'] . ' in file ' . __FILE__ . ' at line ' . __LINE__], false, ApplicationError::MethodNotAllowed->value);
            return;
        }
        try {
            $this->renderJson(['message' => $this->pageDataHelper->get_($id)], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function saveNavbarItem(): void
    {
        if (!($this->application->getConnectedUser()->get()->isNavbarDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
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
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function updateNavbarPositions(): void
    {
        if (!($this->application->getConnectedUser()->get()->isNavbarDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
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
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }
}
