<?php

declare(strict_types=1);

namespace app\apis;

use InvalidArgumentException;
use stdClass;
use Throwable;
use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
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

    public function participantsSupplies(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $eventId = WebApp::getFiltered(
            'eventId',
            FilterInputRule::Int->value,
            $this->application->getFlight()->request()->query->getData()
        );
        if ($eventId === null || !is_numeric($eventId)) {
            $this->renderJsonBadRequest("Invalid parameters", __FILE__, __LINE__);
            return;
        }
        $eventId = (int)$eventId;
        $userEmail = $person->Email;
        try {
            $this->render(
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

    public function updateSupply(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $input = $this->getJsonInput();
            $this->validateSupplyData($input);
            $apiResponse = $this->doUpdateSupply(
                To::int($input['eventId'] ?? null),
                $this->application->getConnectedUser()->person->Email ?? '',
                To::int($input['needId'] ?? null),
                To::int($input['supply'] ?? null)
            );
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
        } catch (UnauthorizedAccessException $e) {
            $this->renderJsonForbidden($e->getFile(), $e->getLine());
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region Private functions

    private function doUpdateSupply(int $eventId, string $userEmail, int $needId, int $supply): ApiResponse
    {
        if (!$this->eventDataHelper->isUserRegistered($eventId, $userEmail)) {
            return new ApiResponse(false, ApplicationError::BadRequest->value);
        }

        $success = $this->eventDataHelper->updateUserSupply($eventId, $userEmail, $needId, $supply);
        if (!$success) {
            return new ApiResponse(false, ApplicationError::BadRequest->value);
        }

        $eventNeeds = $this->eventDataHelper->getEventNeeds($eventId);
        $updatedNeed = $this->findUpdatedNeed($eventNeeds, $needId);
        return new ApiResponse(true, ApplicationError::Ok->value, ['updatedNeed' => $updatedNeed], 'Apport mis à jour avec succès');
    }

    /**
     * @param array<int, stdClass> $eventNeeds
     * @return array{id: int, providedQuantity: int|float, requiredQuantity: int|float, percentage: int|float}|null
     */
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

    /**
     * @param array<string, mixed> $data
     */
    private function validateSupplyData(array $data): void
    {
        $eventId = $data['eventId'] ?? null;
        $needId = $data['needId'] ?? null;
        $supply = To::int($data['supply'] ?? 0);

        if (!$eventId || !$needId || $supply < 0) {
            throw new InvalidArgumentException("Invalid parameters");
        }
    }
}
