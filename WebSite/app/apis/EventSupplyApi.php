<?php

declare(strict_types=1);

namespace app\apis;

use InvalidArgumentException;
use Throwable;
use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\exceptions\QueryException;
use app\exceptions\UnauthorizedAccessException;
use app\models\EventNeedDataHelper;
use app\models\EventParticipationDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\valueObjects\ApiResponse;
use app\modules\Event\valueObjects\EventNeedRow;

final class EventSupplyApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly EventNeedDataHelper $eventNeedDataHelper,
        private readonly EventParticipationDataHelper $eventParticipationDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function participantsSupplies(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
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
        $eventId = To::int($eventId);
        $userEmail = $this->application->getConnectedUser(true)->person->email;
        try {
            $this->render(
                'Event/views/participants-supplies_partial.latte',
                [
                    'participantSupplies' => $this->eventNeedDataHelper->getParticipantSupplies($eventId),
                    'isRegistered' => $this->eventParticipationDataHelper->isUserRegistered($eventId, $userEmail),
                ]
            );
        } catch (Throwable $e) {
            http_response_code(500);
            echo "<div class='alert alert-danger'>"
                . htmlspecialchars($this->languagesDataHelper->translate('common.error_prefix') . ' ' . $e->getMessage())
                . '</div>';
        }
    }

    public function updateSupply(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        try {
            $input = $this->getJsonInput();
            $this->validateSupplyData($input);
            $apiResponse = $this->doUpdateSupply(
                To::int($input['eventId'] ?? null),
                $this->application->getConnectedUser()->person->email ?? '',
                To::int($input['needId'] ?? null),
                To::int($input['supply'] ?? null)
            );
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode, To::str($apiResponse->message));
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
        } catch (UnauthorizedAccessException $e) {
            $this->renderJsonForbidden($e->getFile(), $e->getLine());
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    #region Private functions

    private function doUpdateSupply(int $eventId, string $userEmail, int $needId, int $supply): ApiResponse
    {
        if (!$this->eventParticipationDataHelper->isUserRegistered($eventId, $userEmail)) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'User not registered');
        }

        $success = $this->eventNeedDataHelper->updateUserSupply($eventId, $userEmail, $needId, $supply);
        if (!$success) {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Update error');
        }

        $eventNeeds = $this->eventNeedDataHelper->getEventNeeds($eventId);
        $updatedNeed = $this->findUpdatedNeed($eventNeeds, $needId);

        return new ApiResponse(
            true,
            ApplicationError::Ok->value,
            ['updatedNeed' => $updatedNeed],
            $this->languagesDataHelper->translate('event.supply.updated')
        );
    }

    /**
     * @param list<EventNeedRow> $eventNeeds
     * @return array{id: int, providedQuantity: int|float, requiredQuantity: int|float, percentage: int|float}|null
     */
    private function findUpdatedNeed(array $eventNeeds, int $needId): ?array
    {
        foreach ($eventNeeds as $need) {
            if ($need->id !== $needId) {
                continue;
            }

            return [
                'id' => $need->id,
                'providedQuantity' => $need->providedQuantity,
                'requiredQuantity' => $need->requiredQuantity,
                'percentage' => $need->requiredQuantity > 0
                    ? min(100, ($need->providedQuantity / $need->requiredQuantity) * 100)
                    : 0,
            ];
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
