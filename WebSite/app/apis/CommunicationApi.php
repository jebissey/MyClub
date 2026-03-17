<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\interfaces\EmailQuotaTrackerInterface;
use app\models\DataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\EmailService;

class CommunicationApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private readonly EmailService $emailService,
        private readonly ?EmailQuotaTrackerInterface  $quotaTracker,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function getQuota(): void
    {
        if (!$this->connectedUser->isCommunicationManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $this->renderJsonOk($this->buildQuotaStats());
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getMembers(): void
    {
        if (!$this->connectedUser->isCommunicationManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $presentation = isset($data['presentation']) ? (bool)$data['presentation'] : null;
            $password     = isset($data['password'])     ? (bool)$data['password']     : null;
            $inPublicMap  = isset($data['inPublicMap'])  ? (bool)$data['inPublicMap']  : null;
            $desactivated = isset($data['desactivated']) ? (bool)$data['desactivated'] : null;

            $members = $this->personDataHelper->getPersonsForCommunication(
                groupId     : isset($data['groupId']) ? (int)$data['groupId'] : null,
                presentation: $presentation,
                password    : $password,
                inPublicMap : $inPublicMap,
                desactivated: $desactivated,
            );

            $this->renderJsonOk([
                'members' => array_values(array_map(fn($m) => [
                    'id'    => $m->Id,
                    'name'  => trim(($m->FirstName ?? '') . ' ' . ($m->LastName ?? '')),
                    'email' => $m->Email ?? '',
                ], $members)),
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region Private methods
    private function buildQuotaStats(): array
    {
        $dailySent   = $this->quotaTracker?->getDailySent() ?? 0;
        $monthlySent = $this->quotaTracker?->getMonthlySent() ?? 0;
        $config      = $this->emailService->getSmtpConfig();

        return [
            'dailySent'        => $dailySent,
            'dailyLimit'       => $config->dailyLimit,
            'dailyRemaining'   => $config->dailyLimit   !== null ? max(0, $config->dailyLimit   - $dailySent)   : null,
            'monthlySent'      => $monthlySent,
            'monthlyLimit'     => $config->monthlyLimit,
            'monthlyRemaining' => $config->monthlyLimit !== null ? max(0, $config->monthlyLimit - $monthlySent) : null,
        ];
    }
}
