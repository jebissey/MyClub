<?php
declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\PersonDataHelper;
use app\valueObjects\ApiResponse;

class EventNeedApi extends AbstractApi
{
    public function __construct(
        Application $application,
        private EventNeedDataHelper $eventNeedDataHelper,
        private EventDataHelper $eventDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser,$dataHelper, $personDataHelper);
    }

    public function deleteNeed(int $id): void
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
            $deletedRows = $this->dataHelper->delete('Need', ['Id' => $id]);
            if ($deletedRows === 1) $this->renderJson([], true, ApplicationError::Ok->value);
            else  $this->renderJson([], false, ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getEventNeeds(int $id): void
    {
        if (!$this->application->getConnectedUser()->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = new ApiResponse(true, ApplicationError::Ok->value, ['needs' => $this->eventNeedDataHelper->needsForEvent($id)]);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function saveNeed(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        try {
            $this->saveNeed_($data);
            $this->renderJson([], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    #region Private functions
    private function saveNeed_(array $data): ApiResponse
    {
        if (empty($data['label']))          return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Missing parameter label');
        if (empty($data['name']))           return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Missing parameter name');
        if (!($data['idNeedType'] ?? null)) return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Missing parameter idNeedType');
        $needData = [
            'Label' => $data['label'],
            'Name' => $data['name'],
            'ParticipantDependent' => intval($data['participantDependent'] ?? 0),
            'IdNeedType' => $data['idNeedType']
        ];
        $result = $this->dataHelper->set('Need', $needData, $data['id'] == null ? [] : ['Id' => $data['id']]);
        $success = is_bool($result) ? $result : (is_int($result) ? true : Application::unreachable($result, __FILE__, __LINE__));
        return new ApiResponse($success, $success ? ApplicationError::Ok->value : ApplicationError::Error->value);
    }
}
