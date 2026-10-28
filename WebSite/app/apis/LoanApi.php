<?php

declare(strict_types=1);

namespace app\apis;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\LanguagesDataHelper;
use app\models\LoanDataHelper;
use app\models\PersonDataHelper;

final class LoanApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly LoanDataHelper $loanDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    #region LoanDesigner functions
    public function getItems(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $this->renderJsonOk($this->loanDataHelper->getAllItems());
        }
    }

    public function getItem(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $item = $this->loanDataHelper->getItem($id);
            if (!$item) {
                $this->renderJsonBadRequest("Item {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk($item);
        }
    }

    public function saveItem(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $data = $this->getJsonInput();
            $name = trim(To::str($data['name'] ?? ''));
            if ($name === '') {
                $this->renderJsonBadRequest("Name is required", __FILE__, __LINE__);
                return;
            }

            $id = $this->loanDataHelper->saveItem($data);
            $this->renderJsonOk(['id' => $id]);
        }
    }

    public function deleteItem(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $deleted = $this->loanDataHelper->deleteItem($id);
            if (!$deleted) {
                $this->renderJsonBadRequest('Cannot delete: active loans or reservations exist for this item.', __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk([]);
        }
    }

    #region LoanManager functions
    public function getLoans(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $this->loanDataHelper->updateOverdueLoans();

            $status = WebApp::getFiltered(
                'status',
                FilterInputRule::String->value,
                $this->application->getFlight()->request()->query->getData()
            );

            // Missing or invalid status means "no filter"
            $this->renderJsonOk($this->loanDataHelper->getAllLoans(is_string($status) ? $status : ''));
        }
    }

    public function getLoan(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $loan = $this->loanDataHelper->getLoan($id);
            if (!$loan) {
                $this->renderJsonBadRequest("Loan {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk($loan);
        }
    }

    public function saveLoan(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isLoanManager(), __FILE__, __LINE__)) {
            $data = $this->getJsonInput();
            foreach (['itemId', 'borrowerId', 'lenderId', 'loanDate', 'dueDate', 'quantity'] as $field) {
                if (empty($data[$field])) {
                    $this->renderJsonBadRequest("Field '{$field}' is required", __FILE__, __LINE__);
                    return;
                }
            }
            $available = $this->loanDataHelper->getAvailableQtyForLoan(
                To::int($data['itemId']),
                To::str($data['loanDate']),
                To::str($data['dueDate']),
                empty($data['id']) ? null : To::int($data['id'])
            );
            if ($available < To::int($data['quantity'])) {
                $this->renderJsonBadRequest('Requested quantity exceeds available stock.', __FILE__, __LINE__);
                return;
            }

            $id = $this->loanDataHelper->saveLoan($data);
            $this->renderJsonOk(['id' => $id]);
        }
    }

    public function returnLoan(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isLoanManager(), __FILE__, __LINE__)) {
            $data        = $this->getJsonInput();
            $returnDate  = To::str($data['returnDate'] ?? date('Y-m-d'));
            $returnedTo  = To::int($data['returnedToId'] ?? 0);

            if ($returnedTo === 0) {
                $this->renderJsonBadRequest("Invalid returnedToId", __FILE__, __LINE__);
                return;
            }

            $ok = $this->loanDataHelper->setLoanReturn($id, $returnDate, $returnedTo);
            if (!$ok) {
                $this->renderJsonBadRequest("Loan {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk([]);
        }
    }

    public function cancelLoan(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isLoanManager(), __FILE__, __LINE__)) {
            $ok = $this->loanDataHelper->cancelLoan($id);
            if (!$ok) {
                $this->renderJsonBadRequest("Loan {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk([]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // RESERVATIONS – any connected user
    // ══════════════════════════════════════════════════════════════════════════

    public function getReservations(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoan(), __FILE__, __LINE__)) {
            $user = $this->application->getConnectedUser();
            // A manager sees everything, a regular user only sees their own
            $userId = $user->isLoanManager() ? 0 : $user->person->id ?? 0;
            $this->renderJsonOk($this->loanDataHelper->getAllReservations($userId));
        }
    }

    public function getReservation(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            $res = $this->loanDataHelper->getReservation($id);
            if (!$res) {
                $this->renderJsonBadRequest("Reservation {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk($res);
        }
    }

    public function saveReservation(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            $data = $this->getJsonInput();

            foreach (['itemId', 'reservationDate', 'startTime', 'endTime', 'quantity'] as $field) {
                if (empty($data[$field])) {
                    $this->renderJsonBadRequest("Field '{$field}' is required", __FILE__, __LINE__);
                    return;
                }
            }

            // Force the current user for a standard reservation
            $user = $this->application->getConnectedUser();
            if (!$user->isLoanManager()) {
                $data['userId'] = $user->person->id ?? 0;
            }

            // Availability check
            $available = $this->loanDataHelper->getAvailableQtyForReservation(
                To::int($data['itemId']),
                To::str($data['reservationDate']),
                To::str($data['startTime']),
                To::str($data['endTime']),
                empty($data['id']) ? null : To::int($data['id'])
            );
            if ($available < To::int($data['quantity'])) {
                $this->renderJsonBadRequest(
                    'Requested quantity exceeds available stock.',
                    __FILE__,
                    __LINE__
                );
                return;
            }

            $id = $this->loanDataHelper->saveReservation($data);
            $this->renderJsonOk(['id' => $id]);
        }
    }

    public function cancelReservation(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            $user   = $this->application->getConnectedUser();
            $userId = $user->isLoanManager() ? 0 : $user->person->id ?? 0;

            $ok = $this->loanDataHelper->cancelReservation($id, $userId);
            if (!$ok) {
                $this->renderJsonBadRequest("Reservation {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk([]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CALENDAR & AVAILABILITY
    // ══════════════════════════════════════════════════════════════════════════

    public function getCalendarEvents(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            $schema = [
                'start' => FilterInputRule::DateTime->value,
                'end'   => FilterInputRule::DateTime->value,
            ];
            $input = WebApp::filterInput($schema, $this->application->getFlight()->request()->query->getData());

            $start = is_string($input['start']) ? $input['start'] : date('Y-m-01');
            $end   = is_string($input['end']) ? $input['end'] : date('Y-m-t');

            $this->renderJsonOk($this->loanDataHelper->getCalendarEvents($start, $end));
        }
    }

    public function getAvailability(int $itemId): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $schema = [
                'type'    => ['loan', 'reservation'],
                'date'    => FilterInputRule::DateTime->value,
                'start'   => FilterInputRule::DateTime->value,
                'end'     => FilterInputRule::DateTime->value,
                'dueDate' => FilterInputRule::DateTime->value,
            ];
            $input = WebApp::filterInput($schema, $this->application->getFlight()->request()->query->getData());

            $type  = is_string($input['type']) ? $input['type'] : 'loan';
            $date  = is_string($input['date']) ? $input['date'] : date('Y-m-d');
            $start = is_string($input['start']) ? $input['start'] : '00:00';
            $end   = is_string($input['end']) ? $input['end'] : '23:59';

            if ($type === 'loan') {
                $due = is_string($input['dueDate']) ? $input['dueDate'] : $date;
                $qty = $this->loanDataHelper->getAvailableQtyForLoan($itemId, $date, $due);
            } else {
                $qty = $this->loanDataHelper->getAvailableQtyForReservation($itemId, $date, $start, $end);
            }
            $this->renderJsonOk(['available' => $qty]);
        }
    }
}
