<?php

declare(strict_types=1);

namespace app\modules\Membership;

use app\helpers\Application;
use app\models\MembershipDataHelper;
use app\modules\Common\AbstractController;
use app\modules\HelloAsso\services\HelloAssoService;

class MembershipController extends AbstractController
{
    public function __construct(
        Application $application,
        private MembershipDataHelper $membershipDataHelper,
    ) {
        parent::__construct($application);
    }

	// ─── Pages ────────────────────────────────────────────────────────────────

    /**
     * GET /membership
     * Shows the current-season membership status and the pay button.
     */
    public function index(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $user     = $this->application->getConnectedUser();
        $personId = (int)$user->person->Id;
        $season   = $this->membershipDataHelper->currentSeason();

        $current  = $this->membershipDataHelper->getForPersonAndSeason($personId, $season);
        $history  = $this->membershipDataHelper->getAllForPerson($personId);

        $paymentFeedback = $_GET['payment'] ?? null; // 'success' | 'error' | null

        // ── HelloAsso widget URL ──────────────────────────────────────────────
        $widgetUrl = null;
        if (!$current || $current->Status !== 'paid') {
            $widgetUrl = HelloAssoService::getInstance($this->dataHelper)->getWidgetUrl(
                formType: 'adhesions',
                formSlug: 'saison-2026-2027',
                options: [
                    'firstName' => $user->person->FirstName ?? '',
                    'lastName'  => $user->person->LastName  ?? '',
                    'email'     => $user->person->Email     ?? '',
                ],
            );
        }

        $this->render('Membership/views/index.latte', $this->getAllParams([
            'season'          => $season,
            'current'         => $current,
            'history'         => $history,
            'amountCents'     => $this->membershipDataHelper->getAmountCents(),
            'paymentFeedback' => $paymentFeedback,
            'translations'    => $this->translations_(),
            'activeTab'       => 'membership',
            'btn_Parent'      => '/user',
            'btn_HistoryBack' => true,
            'page'            => $this->application->getConnectedUser()->getPage(1),
            'widgetUrl'       => $widgetUrl,
        ]));
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function translations_(): array
    {
        $keys = [
            'nav.my',
            'title',
            'season',
            'status',
            'amount',
            'pay',
            'status.pending',
            'status.paid',
            'status.cancelled',
            'already_paid',
            'no_membership',
            'payment_success',
            'payment_error',
        ];
        return $this->translations($keys, 'membership.');
    }
}
