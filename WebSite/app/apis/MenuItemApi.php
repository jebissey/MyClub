<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\Data;
use app\models\DataHelper;
use app\models\MenuItemDataHelper;
use app\models\PersonDataHelper;

class MenuItemApi extends AbstractApi
{
    public function __construct(Application $application, private MenuItemDataHelper $menuItemDataHelper, ConnectedUser $connectedUser, DataHelper $dataHelper, PersonDataHelper $personDataHelper)
    {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function deleteItem(int $id)
    {
        if (!($this->application->getConnectedUser()->isMenuDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $result = $this->menuItemDataHelper->del($id);
            $this->renderJson([], $result >= 1, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getMenuItem(int $id): void
    {
        if (!($this->application->getConnectedUser()->isMenuDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJson(['message' => 'Not allowed method: ' . $_SERVER['REQUEST_METHOD'] . ' in file ' . __FILE__ . ' at line ' . __LINE__], false, ApplicationError::MethodNotAllowed->value);
            return;
        }
        try {
            $this->renderJsonOk(['item' => $this->dataHelper->get('MenuItem', ['Id' => $id])]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function saveMenuItem(): void
    {
        if (!($this->application->getConnectedUser()->isMenuDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!Data::requireFields($data, ['what', 'type', 'label', 'url', 'forMembers', 'forContacts', 'forAnonymous'])) {
            $this->renderJsonBadRequest('Missing required fields', __FILE__, __LINE__);
            return;
        }
        try {
            $this->menuItemDataHelper->insertOrUpdate($data);
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function updatePositions()
    {
        if (!($this->application->getConnectedUser()->isMenuDesigner() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $this->menuItemDataHelper->updates($data['positions']);
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
