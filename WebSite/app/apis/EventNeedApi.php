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
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function deleteNeed(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventDesigner(), __FILE__, __LINE__)) {
            try {
                $deletedRows = $this->dataHelper->delete('Need', ['Id' => $id]);
                if ($deletedRows === 1) {
                    $this->renderJsonOk();
                } else {
                    $this->renderJsonBadRequest('', __FILE__, __LINE__);
                }
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    public function getEventNeeds(int $id): void
    {

        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventDesigner(), __FILE__, __LINE__)) {
            try {
                $apiResponse = new ApiResponse(
                    true,
                    ApplicationError::Ok->value,
                    ['needs' => $this->eventNeedDataHelper->needsForEvent($id)]
                );
                $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    public function saveNeed(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventDesigner(), __FILE__, __LINE__)) {
            $data = $this->getJsonInput();
            try {
                $this->doSaveNeed($data);
                $this->renderJsonOk();
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    #region Private functions
    /**
     * @param array<string, mixed> $data Raw decoded JSON body; shape is validated at runtime below,
     *                                    not by PHPStan, since it comes from getJsonInput().
     */
    private function doSaveNeed(array $data): ApiResponse
    {
        $label = $data['label'] ?? null;
        $name = $data['name'] ?? null;
        $idNeedType = $data['idNeedType'] ?? null;
        $id = $data['id'] ?? null;

        if (!is_string($label) || $label === '') {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Missing parameter label');
        }
        if (!is_string($name) || $name === '') {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Missing parameter name');
        }
        if (!is_int($idNeedType) && !(is_string($idNeedType) && ctype_digit($idNeedType))) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Missing parameter idNeedType');
        }
        if ($id !== null && !is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Invalid parameter id');
        }

        $participantDependent = $data['participantDependent'] ?? 0;
        if (!is_int($participantDependent) && !(is_string($participantDependent) && is_numeric($participantDependent))) {
            $participantDependent = 0;
        }

        $needData = [
            'Label' => $label,
            'Name' => $name,
            'ParticipantDependent' => intval($participantDependent),
            'IdNeedType' => intval($idNeedType)
        ];
        $result = $this->dataHelper->set('Need', $needData, $id === null ? [] : ['Id' => intval($id)]);
        $success = is_bool($result) ? $result : ($result > 0 ? true : Application::unreachable($result, __FILE__, __LINE__));
        return new ApiResponse($success, $success ? ApplicationError::Ok->value : ApplicationError::Error->value);
    }
}
