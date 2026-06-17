<?php

declare(strict_types=1);

namespace app\apis;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\MembershipDataHelper;
use app\models\PersonDataHelper;
use app\modules\HelloAsso\services\HelloAssoService;

class HelloAssoApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private MembershipDataHelper $membershipDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

	// ─── POST /api/helloAsso/checkout ───────────────────────────────────────

    /**
     * Initiates a HelloAsso checkout intent for the current season.
     * Returns { success, data: { redirectUrl } }.
     */
    public function checkout(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected())) {
            return;
        }

        $user   = $this->application->getConnectedUser();
        $person = $user->person;
        $season = $this->membershipDataHelper->currentSeason();

        // Guard: already paid this season
        $existing = $this->membershipDataHelper->getForPersonAndSeason((int)$person->Id, $season);
        if ($existing && $existing->Status === 'paid') {
            $this->renderJsonBadRequest('Membership already paid for this season.', __FILE__, __LINE__);
            return;
        }

        // Cancel any stale pending row before creating a fresh one
        if ($existing && $existing->Status === 'pending') {
            $this->membershipDataHelper->cancel((int)$existing->Id);
        }

        $amountCents  = $this->membershipDataHelper->getAmountCents();
        $membershipId = $this->membershipDataHelper->createPending(
            (int)$person->Id,
            $season,
            $amountCents
        );

        $root = \app\helpers\Application::$root;

        try {
            $result = HelloAssoService::getInstance($this->dataHelper)
                ->createCheckoutIntent(
                    amountCents: $amountCents,
                    description: "Adhésion saison {$season}",
                    returnUrl: "{$root}/membership/return?mid={$membershipId}&status=success",
                    errorUrl: "{$root}/membership/return?mid={$membershipId}&status=error",
                    payer: [
                        'firstName' => $person->FirstName ?? '',
                        'lastName'  => $person->LastName  ?? '',
                        'email'     => $person->Email     ?? '',
                    ],
                );

            $this->membershipDataHelper->attachCheckoutIntent(
                $membershipId,
                $result['checkoutIntentId']
            );

            $this->renderJsonOk(['redirectUrl' => $result['redirectUrl']]);
        } catch (\RuntimeException $e) {
            $this->membershipDataHelper->cancel($membershipId);
            $this->renderJsonBadRequest($e->getMessage(), __FILE__, __LINE__);
        }
    }

	// ─── POST /api/helloAsso/webhook ────────────────────────────────────────

    /**
     * HelloAsso webhook endpoint (no authentication: public URL).
     * HelloAsso sends a POST with JSON body on every payment event.
     *
     * Expected payload subset:
     *   { "eventType": "Payment", "data": { "order": { "id": "..." }, "checkoutIntentId": "..." } }
     */
    public function webhook(): void
    {
        // Webhook is called by HelloAsso, not by a browser – skip user auth
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $eventType = $data['eventType'] ?? '';
        $orderId   = (string)($data['data']['order']['id'] ?? '');
        $intentId  = (string)($data['data']['checkoutIntentId'] ?? '');

        if ($eventType !== 'Payment' || $intentId === '') {
            // Not an event we handle – acknowledge silently
            http_response_code(200);
            echo json_encode(['ok' => true]);
            return;
        }

        $ok = $this->membershipDataHelper->markPaidByIntentId($intentId, $orderId);

        http_response_code(200);
        echo json_encode(['ok' => $ok]);
    }

	// ─── GET /api/helloAsso/return ──────────────────────────────────────────

    /**
     * Return URL after HelloAsso payment page (redirect, not AJAX).
     * Query params: mid (membership id), status (success|error)
     */
    public function paymentReturn(): void
    {
        $status = $_GET['status'] ?? 'error';

        if ($status === 'success') {
            $this->application->getFlight()->redirect('/membership?payment=success');
        } else {
            $this->application->getFlight()->redirect('/membership?payment=error');
        }
    }
}
