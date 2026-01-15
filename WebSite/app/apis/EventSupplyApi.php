<?php

declare(strict_types=1);

namespace app\apis;

use InvalidArgumentException;
use Throwable;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\exceptions\QueryException;
use app\exceptions\UnauthorizedAccessException;
use app\models\DataHelper;
use app\models\EventDataHelper;
use app\models\PersonDataHelper;
use app\valueObjects\ApiResponse;

class EventSupplyApi extends AbstractApi
{
    public function __construct(
        Application $application,
        private EventDataHelper $eventDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function updateSupply(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $input = $this->getJsonInput();
            $this->validateSupplyData($input);
            $apiResponse = $this->updateSupply_(
                (int)$input['eventId'],
                $this->application->getConnectedUser()->person->Email,
                (int)$input['needId'],
                intval($input['supply'])
            );
            $this->renderJson($apiResponse->data, $apiResponse->success,  $apiResponse->responseCode);
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
        } catch (UnauthorizedAccessException $e) {
            $this->renderJsonForbidden($e->getFile(), $e->getLine());
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function participantsSupplies(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $eventId = WebApp::getFiltered('eventId', FilterInputRule::Int->value, $this->application->getFlight()->request()->query->getData());
        if ($eventId === null || !is_numeric($eventId)) {
            $this->renderJsonBadRequest("Invalid parameters", __FILE__, __LINE__);
            return;
        }
        $eventId = (int)$eventId;
        $userEmail = $person->Email ?? '';
        try {
            $this->application->getLatte()->render(
                'Event/views/participants-supplies_partial.latte',
                [
                    'participantSupplies' => $this->eventDataHelper->getParticipantSupplies($eventId),
                    'isRegistered' => $this->eventDataHelper->isUserRegistered($eventId, $userEmail),
                ]
            );
        } catch (Throwable $e) {
            http_response_code(500);
            echo "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    #region Private functions
    private function validateSupplyData(array $data): void
    {
        $eventId = $data['eventId'] ?? null;
        $needId = $data['needId'] ?? null;
        $supply = intval($data['supply'] ?? 0);

        if (!$eventId || !$needId || $supply < 0) throw new InvalidArgumentException("Invalid parameters");
    }

    private function updateSupply_(int $eventId, string $userEmail, int $needId, int $supply): ApiResponse
    {
        if (!$this->eventDataHelper->isUserRegistered($eventId, $userEmail)) return new ApiResponse(false, ApplicationError::BadRequest->value);

        $success = $this->eventDataHelper->updateUserSupply($eventId, $userEmail, $needId, $supply);
        if (!$success) return new ApiResponse(false, ApplicationError::BadRequest->value);

        $eventNeeds = $this->eventDataHelper->getEventNeeds($eventId);
        $updatedNeed = $this->findUpdatedNeed($eventNeeds, $needId);
        return new ApiResponse(true, ApplicationError::Ok->value, ['updatedNeed' => $updatedNeed], 'Apport mis à jour avec succès');
    }

    private function findUpdatedNeed(array $eventNeeds, int $needId): ?array
    {
        foreach ($eventNeeds as $need) {
            if ($need->Id == $needId) {
                return [
                    'id' => $need->Id,
                    'providedQuantity' => $need->ProvidedQuantity,
                    'requiredQuantity' => $need->RequiredQuantity,
                    'percentage' => $need->RequiredQuantity > 0
                        ? min(100, ($need->ProvidedQuantity / $need->RequiredQuantity) * 100)
                        : 0
                ];
            }
        }
        return null;
    }
}
