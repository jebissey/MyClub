<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
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
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $raw = $this->getJsonInput();

            /** @var array{name?: string, detail?: string, color?: string} $data */
            $data = [
                'name' => To::str($raw['name'] ?? null),
                'detail' => To::str($raw['detail'] ?? null),
                'color' => To::str($raw['color'] ?? null),
            ];

            [$response, $statusCode] = $this->attributeDataHelper->insert($data);
            $this->renderJson($response, true, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function deleteAttribute(int $id): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            [$response, $statusCode] = $this->attributeDataHelper->deleteAttribute($id);
            $this->renderJson($response, true, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getAttributes(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $this->render(
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
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($this->dataHelper->get('EventType', ['Id' => $eventTypeId], 'Id') === false) {
            $this->renderJsonBadRequest("Unknown event type {$eventTypeId}", __FILE__, __LINE__);
            return;
        }
        try {
            $this->renderJsonOk(['attributes' => $this->attributeDataHelper->getAttributesOf($eventTypeId)]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function updateAttribute(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $raw = $this->getJsonInput();

            /** @var array{id: int, name: string, detail: string, color: string} $data */
            $data = [
                'id' => To::int($raw['id'] ?? null),
                'name' => To::str($raw['name'] ?? null),
                'detail' => To::str($raw['detail'] ?? null),
                'color' => To::str($raw['color'] ?? null),
            ];

            $this->attributeDataHelper->update($data);
            $this->renderJsonOk();
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
