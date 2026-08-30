<?php

declare(strict_types=1);

namespace app\modules\Membership;

use app\helpers\Application;
use app\models\MembershipDataHelper;
use app\modules\Common\AbstractController;
use app\modules\HelloAsso\services\HelloAssoService;
use app\modules\Membership\viewModels\MembershipIndexViewModel;

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
        $personId = (int)($user->person->Id ?? 0);
        $season   = $this->membershipDataHelper->currentSeason();

        $current  = $this->membershipDataHelper->getForPersonAndSeason($personId, $season);
        $history  = $this->membershipDataHelper->getAllForPerson($personId);

        $paymentFeedbackRaw = $_GET['payment'] ?? null;
        $paymentFeedback = is_string($paymentFeedbackRaw) ? $paymentFeedbackRaw : null;

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

        $viewModel = new MembershipIndexViewModel(
            season: $season,
            current: $current,
            history: array_values($history),
            amountCents: $this->membershipDataHelper->getAmountCents(),
            paymentFeedback: $paymentFeedback,
            translations: $this->doTranslations(),
            activeTab: 'membership',
            widgetUrl: $widgetUrl,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Membership/views/index.latte', $viewModel->toArray());
    }

    // ─── Private helpers ─────────────────────────────────────────────────────
    /**
     * @return array<string, string>
     */
    private function doTranslations(): array
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
