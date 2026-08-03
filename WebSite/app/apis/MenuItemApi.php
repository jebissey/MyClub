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
    public function __construct(
        Application $application,
        private MenuItemDataHelper $menuItemDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function deleteItem(int $id): void
    {
        if (!$this->application->getConnectedUser()->isMenuDesigner()) {
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
        if (!$this->application->getConnectedUser()->isMenuDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJson(
                ['message' => 'Not allowed method: ' . $_SERVER['REQUEST_METHOD'] . ' in file ' . __FILE__ . ' at line ' . __LINE__],
                false,
                ApplicationError::MethodNotAllowed->value
            );
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
        if (!$this->application->getConnectedUser()->isMenuDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            if (!Data::requireFields($data, ['what', 'type', 'label', 'url', 'forMembers', 'forContacts', 'forAnonymous'])) {
                $this->renderJsonBadRequest('Missing required fields', __FILE__, __LINE__);
                return;
            }

            /**
             * @var array{
             *     what: string,
             *     type: string,
             *     label?: string|null,
             *     icon?: string|null,
             *     url?: string|null,
             *     idGroup?: int|null,
             *     parentId?: int|null,
             *     forMembers?: bool,
             *     forContacts?: bool,
             *     forAnonymous?: bool,
             *     id?: int,
             *     position?: int
             * } $menuItemData
             */
            $menuItemData = [
                'what' => (string) $data['what'],
                'type' => (string) $data['type'],
                'label' => isset($data['label']) ? (string) $data['label'] : null,
                'icon' => isset($data['icon']) ? (string) $data['icon'] : null,
                'url' => isset($data['url']) ? (string) $data['url'] : null,
                'idGroup' => isset($data['idGroup']) ? (int) $data['idGroup'] : null,
                'parentId' => isset($data['parentId']) ? (int) $data['parentId'] : null,
                'forMembers' => (bool) ($data['forMembers'] ?? false),
                'forContacts' => (bool) ($data['forContacts'] ?? false),
                'forAnonymous' => (bool) ($data['forAnonymous'] ?? false),
            ];
            if (isset($data['id'])) {
                $menuItemData['id'] = (int) $data['id'];
            }
            if (isset($data['position'])) {
                $menuItemData['position'] = (int) $data['position'];
            }

            $this->menuItemDataHelper->insertOrUpdate($menuItemData);
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function updatePositions(): void
    {
        if (!$this->application->getConnectedUser()->isMenuDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $this->menuItemDataHelper->updates($data['positions']);
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
