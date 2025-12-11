<?php

declare(strict_types=1);

namespace app\apis;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\enums\Karaoke;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\PersonDataHelper;
use app\models\KaraokeDataHelper;

class KaraokeApi extends AbstractApi
{
    private string $sessionId;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private KaraokeDataHelper $karaokeDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function handleApiRequest(): void
    {
        $schema = [
            'songId' => FilterInputRule::String->value,
            'action' => FilterInputRule::String->value,
            'clientId' => FilterInputRule::String->value,
        ];
        $requestParam = WebApp::filterInput($schema, $this->application->getFlight()->request()->query->getData());
        $songId = $requestParam['songId'] ?? '';
        $action = $requestParam['action'] ?? '';
        $clientId = $requestParam['clientId'] ?? '';

        if (empty($songId)) {
            $this->renderJsonError('Song ID required', ApplicationError::BadRequest->value, __FILE__, __LINE__);
            return;
        }
        if (empty($clientId)) {
            $this->renderJsonError('Client ID required', ApplicationError::BadRequest->value, __FILE__, __LINE__);
            return;
        }

        $this->karaokeDataHelper->cleanupOldClients();
        $this->sessionId = 'song_' . $songId;

        switch ($action) {
            case Karaoke::Register->value:
                $this->handleRegister($clientId, $songId);
                break;

            case Karaoke::Heartbeat->value:
                $this->handleHeartbeat($clientId);
                break;

            case Karaoke::GetStatus->value:
                $this->handleGetStatus($clientId);
                break;

            case Karaoke::StartCountdown->value:
                $this->handleStartCountdown($clientId);
                break;

            case Karaoke::Disconnect->value:
                $this->handleDisconnect($clientId);
                break;

            case Karaoke::Cleanup->value:
                $this->handleCleanup();
                break;

            default:
                $this->renderJsonError('Invalid action', ApplicationError::BadRequest->value, __FILE__, __LINE__);
                break;
        }
    }

    #region Private functions
    private function handleRegister(string $clientId, string $songId): void
    {
        $sessionDbId = $this->karaokeDataHelper->getOrCreateSession($this->sessionId, $songId);
        $count = $this->karaokeDataHelper->countActiveClients($sessionDbId);
        $isHost = ($count == 0);
        $this->karaokeDataHelper->registerClient($clientId, $sessionDbId, $isHost);

        $this->renderJsonOk([
            'serverTime' => time(),
            'isHost' => $isHost,
            'clientsCount' => $count + 1
        ]);
    }

    private function handleHeartbeat(string $clientId): void
    {
        $this->karaokeDataHelper->updateHeartbeat($clientId);
        $this->renderJsonOk(['serverTime' => time()]);
    }

    private function handleGetStatus(string $clientId): void
    {
        $session = $this->karaokeDataHelper->getSessionBySessionId($this->sessionId);
        if (!$session) {
            $this->renderJsonOk([
                'serverTime' => time(),
                'isHost' => false,
                'clientsCount' => 0,
                'hasActiveSession' => false,
                'status' => 'waiting',
                'countdownStart' => null,
                'playStartTime' => null
            ]);
            return;
        }

        $sessionDbId = (int)$session->Id;
        $isHost = $this->karaokeDataHelper->isClientHost($clientId, $sessionDbId);
        $clientsCount = $this->karaokeDataHelper->countActiveClients($sessionDbId);

        $this->renderJsonOk([
            'serverTime' => time(),
            'isHost' => $isHost,
            'clientsCount' => $clientsCount,
            'hasActiveSession' => $clientsCount > 0,
            'status' => $session->Status,
            'countdownStart' => $session->CountdownStart,
            'playStartTime' => $session->PlayStartTime
        ]);
    }

    private function handleStartCountdown(string $clientId): void
    {
        $session = $this->karaokeDataHelper->getSessionBySessionId($this->sessionId);
        if (!$session) {
            $this->renderJsonError('Session not found', ApplicationError::PageNotFound->value, __FILE__, __LINE__);
            return;
        }

        $sessionDbId = (int)$session->Id;
        if (!$this->karaokeDataHelper->isClientHost($clientId, $sessionDbId)) {
            $this->renderJsonError('Not host', ApplicationError::Forbidden->value, __FILE__, __LINE__);
            return;
        }

        $this->karaokeDataHelper->startCountdown($sessionDbId);
        $updatedSession = $this->karaokeDataHelper->getSessionById($sessionDbId);

        $this->renderJsonOk([
            'serverTime' => time(),
            'countdownStart' => $updatedSession->CountdownStart,
            'playStartTime' => $updatedSession->PlayStartTime
        ]);
    }

    private function handleDisconnect(string $clientId): void
    {
        $this->karaokeDataHelper->disconnectClient($clientId);
        $session = $this->karaokeDataHelper->getSessionBySessionId($this->sessionId);

        if ($session) {
            $sessionDbId = (int)$session->Id;
            $this->karaokeDataHelper->deleteSessionIfEmpty($sessionDbId);
        }

        $this->renderJsonOk(['serverTime' => time()]);
    }

    private function handleCleanup(): void
    {
        $this->karaokeDataHelper->cleanup();
        $this->renderJsonOk(['serverTime' => time()]);
    }
}
