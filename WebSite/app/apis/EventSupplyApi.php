<?php

namespace app\apis;

use InvalidArgumentException;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\exceptions\QueryException;
use app\exceptions\UnauthorizedAccessException;
use app\models\EventDataHelper;
use app\valueObjects\ApiResponse;

class EventSupplyApi extends AbstractApi
{
    public function __construct(Application $application, private EventDataHelper $eventDataHelper)
    {
        parent::__construct($application);
    }

    public function updateSupply(): void
    {
        if ($this->connectedUser->get()->person === null) {
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
                $input['eventId'],
                $this->connectedUser->get()->person->Email,
                $input['needId'],
                intval($input['supply'])
            );
            $this->renderJson([$apiResponse->data], $apiResponse->success,  $apiResponse->responseCode);
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), __FILE__, __LINE__);
        } catch (UnauthorizedAccessException $e) {
            $this->renderJsonForbidden($e->getMessage(), __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
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
