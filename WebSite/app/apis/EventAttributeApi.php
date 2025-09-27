<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\AttributeDataHelper;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class EventAttributeApi extends AbstractApi
{
    public function __construct(
        Application $application,
        private AttributeDataHelper $attributeDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function createAttribute(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            [$response, $statusCode] = $this->attributeDataHelper->insert($data);
            $this->renderJson($response, true, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function deleteAttribute(int $id): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            [$response, $statusCode] = $this->attributeDataHelper->delete_($id);
            $this->renderJson($response, true, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getAttributes(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $this->application->getLatte()->render(
                'Event/views/attributes-list_partial.latte',
                ['attributes' => $this->attributeDataHelper->getAttributes()]
            );
        } catch (Throwable $e) {
            http_response_code(500);
            echo "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    public function getAttributesByEventType(int $eventTypeId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($this->dataHelper->get('EventType', ['Id' => $eventTypeId], 'Id') === false) {
            $this->renderJsonBadRequest("Unknown event type {$eventTypeId}", __FILE__, __LINE__);
            return;
        }
        try {
            $this->renderJson(['attributes' => $this->attributeDataHelper->getAttributesOf($eventTypeId)], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function updateAttribute(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $this->attributeDataHelper->update($data);
            $this->renderJson([], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }
}
