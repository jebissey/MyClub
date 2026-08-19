<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
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
        if (WebApp::getRequestMethod() !== 'POST') {
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
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJson(
                ['message' => 'Not allowed method: ' . WebApp::getRequestMethod() . ' in file ' . __FILE__ . ' at line ' . __LINE__],
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
        if (WebApp::getRequestMethod() !== 'POST') {
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
                'what' => To::str($data['what']),
                'type' => To::str($data['type']),
                'label' => isset($data['label']) ? To::str($data['label']) : null,
                'icon' => isset($data['icon']) ? To::str($data['icon']) : null,
                'url' => isset($data['url']) ? To::str($data['url']) : null,
                'idGroup' => isset($data['idGroup']) ? To::int($data['idGroup']) : null,
                'parentId' => isset($data['parentId']) ? To::int($data['parentId']) : null,
                'forMembers' => (bool) ($data['forMembers'] ?? false),
                'forContacts' => (bool) ($data['forContacts'] ?? false),
                'forAnonymous' => (bool) ($data['forAnonymous'] ?? false),
            ];
            if (isset($data['id'])) {
                $menuItemData['id'] = To::int($data['id']);
            }
            if (isset($data['position'])) {
                $menuItemData['position'] = To::int($data['position']);
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
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            if (!isset($data['positions']) || !is_array($data['positions'])) {
                $this->renderJsonBadRequest('Missing or invalid positions field', __FILE__, __LINE__);
                return;
            }

            /** @var array<int, int> $positions */
            $positions = array_values(array_map(
                static fn(mixed $v): int => To::int($v),
                $data['positions']
            ));

            $this->menuItemDataHelper->updates($positions);
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
