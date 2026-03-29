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

                if (empty($recipientIds) || $subject === '' || $content === '') {
                    $this->renderJsonBadRequest('Champs obligatoires manquants.', __FILE__, __LINE__);
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
                    $this->renderJsonBadRequest('Aucun destinataire valide trouvé.', __FILE__, __LINE__);
                    return;
                }

                // Vérification quota avant envoi
                $config = $this->emailService->getSmtpConfig();
                $count  = count($bcc);
                if ($config->dailyLimit !== null && ($this->quotaTracker?->getDailySent() ?? 0) + $count > $config->dailyLimit) {
                    $this->renderJsonOk([...$this->buildQuotaStats(), 'quotaHit' => true, 'toast' => 'Quota journalier dépassé.']);
                    return;
                }
                if ($config->monthlyLimit !== null && ($this->quotaTracker?->getMonthlySent() ?? 0) + $count > $config->monthlyLimit) {
                    $this->renderJsonOk([...$this->buildQuotaStats(), 'quotaHit' => true, 'toast' => 'Quota mensuel dépassé.']);
                    return;
                }

                $from = $config->getSenderAddress($this->connectedUser->person->Email);
                $emailMessage = new EmailMessage(
                    from: $from,
                    to: $from,
                    bcc: $bcc,
                    subject: $subject,
                    body: $content,
                    isHtml: true,
                );

                $emailSent = $this->emailService->send($emailMessage);
                $this->quotaTracker?->increment($count);

                if ($emailSent) {
                    $this->renderJsonOk([
                        ...$this->buildQuotaStats(),
                        'toast'   => 'Message envoyé avec succès à ' . $count . ' destinataire(s) en copie cachée.',
                    ]);
                } else {
                    $this->renderJsonError('L\'envoi a échoué. Veuillez réessayer ou contacter l\'administrateur.', ApplicationError::Error->value, __FILE__, __LINE__);
                }
            } catch (EmailException $e) {
                $this->renderJsonError('Envoi impossible : ' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
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
}
