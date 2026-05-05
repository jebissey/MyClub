<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\exceptions\EmailException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\interfaces\EmailQuotaTrackerInterface;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\EmailService;
use app\valueObjects\EmailMessage;

class CommunicationApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private readonly EmailService $emailService,
        private readonly ?EmailQuotaTrackerInterface  $quotaTracker,
        private readonly LanguagesDataHelper $languagesDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function getQuota(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isCommunicationManager())) {

            try {
                $this->renderJsonOk($this->buildQuotaStats());
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
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
                groupId: isset($data['groupId']) ? (int)$data['groupId'] : null,
                presentation: $presentation,
                password: $password,
                inPublicMap: $inPublicMap,
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

    public function sendCommunication(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isCommunicationManager())) {

            try {
                $input        = $this->getJsonInput();
                $recipientIds = array_filter(array_map('intval', $input['recipient_ids'] ?? []));
                $subject      = trim($input['subject'] ?? '');
                $content      = trim($input['content'] ?? '');
                $replyToMode  = $input['reply_to'] ?? null;

                if (empty($recipientIds) || $subject === '' || $content === '') {
                    $this->renderJsonBadRequest(
                        $this->languagesDataHelper->translate('communication.api.missing_fields'),
                        __FILE__,
                        __LINE__
                    );
                    return;
                }

                $allMembers = $this->personDataHelper->getAllPersons();
                $indexById  = array_flip($allMembers);
                $bcc = [];
                foreach ($recipientIds as $id) {
                    $email = $indexById[$id] ?? null;
                    if ($email !== null && $email !== '') {
                        $bcc[] = $email;
                    }
                }

                if (empty($bcc)) {
                    $this->renderJsonBadRequest(
                        $this->languagesDataHelper->translate('communication.api.no_valid_recipients'),
                        __FILE__,
                        __LINE__
                    );
                    return;
                }

                $config = $this->emailService->getSmtpConfig();
                $count  = count($bcc);

                if ($config->dailyLimit !== null && ($this->quotaTracker?->getDailySent() ?? 0) + $count > $config->dailyLimit) {
                    $this->renderJsonOk([
                        ...$this->buildQuotaStats(),
                        'quotaHit' => true,
                        'toast'    => $this->languagesDataHelper->translate('communication.api.quota_daily_exceeded'),
                    ]);
                    return;
                }
                if ($config->monthlyLimit !== null && ($this->quotaTracker?->getMonthlySent() ?? 0) + $count > $config->monthlyLimit) {
                    $this->renderJsonOk([
                        ...$this->buildQuotaStats(),
                        'quotaHit' => true,
                        'toast'    => $this->languagesDataHelper->translate('communication.api.quota_monthly_exceeded'),
                    ]);
                    return;
                }

                $from    = $config->getSenderAddress($this->connectedUser->person->Email);
                $replyTo = $this->resolveReplyTo($replyToMode, $config->from ?? '', $this->connectedUser->person->Email ?? '');

                $emailMessage = new EmailMessage(
                    from: $from,
                    to: $from,
                    bcc: $bcc,
                    subject: $subject,
                    body: $content,
                    isHtml: true,
                    replyTo: $replyTo,
                );

                $emailSent = $this->emailService->send($emailMessage);
                $this->quotaTracker?->increment($count);

                if ($emailSent) {
                    $this->renderJsonOk([
                        ...$this->buildQuotaStats(),
                        'toast' => sprintf(
                            $this->languagesDataHelper->translate('communication.api.send_success'),
                            $count
                        ),
                    ]);
                } else {
                    $this->renderJsonError(
                        $this->languagesDataHelper->translate('communication.api.send_failed'),
                        ApplicationError::Error->value,
                        __FILE__,
                        __LINE__
                    );
                }
            } catch (EmailException $e) {
                $this->renderJsonError(
                    $this->languagesDataHelper->translate('communication.api.send_impossible') . $e->getMessage(),
                    ApplicationError::Error->value,
                    $e->getFile(),
                    $e->getLine()
                );
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
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

    private function resolveReplyTo(?string $mode, string $smtpFrom, string $userEmail): ?string
    {
        return match ($mode) {
            'smtp'  => $smtpFrom !== '' ? $smtpFrom : null,
            'user'  => $userEmail !== '' ? $userEmail : null,
            default => $this->buildNoReplyAddress($smtpFrom),
        };
    }

    private function buildNoReplyAddress(string $smtpFrom): ?string
    {
        $atPos  = strrpos($smtpFrom, '@');
        if ($atPos === false) {
            return null;
        }

        $domain = substr($smtpFrom, $atPos + 1);

        return "noreply@{$domain}";
    }
}
