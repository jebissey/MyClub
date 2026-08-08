<?php

declare(strict_types=1);

namespace app\apis;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\LoanDataHelper;
use app\models\PersonDataHelper;

class LoanApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private LoanDataHelper $loanDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
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
            $name = trim(WebApp::toStr($data['name'] ?? ''));
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
            $status = WebApp::toStr($_GET['status'] ?? '');
            $this->renderJsonOk($this->loanDataHelper->getAllLoans($status));
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
                WebApp::toInt($data['itemId']),
                WebApp::toStr($data['loanDate']),
                WebApp::toStr($data['dueDate']),
                empty($data['id']) ? null : WebApp::toInt($data['id'])
            );
            if ($available < WebApp::toInt($data['quantity'])) {
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
            $returnDate  = WebApp::toStr($data['returnDate'] ?? date('Y-m-d'));
            $returnedTo  = WebApp::toInt($data['returnedToId'] ?? 0);

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
    // RÉSERVATIONS – tout utilisateur connecté
    // ══════════════════════════════════════════════════════════════════════════

    public function getReservations(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoan(), __FILE__, __LINE__)) {
            $user = $this->application->getConnectedUser();
            // Un manager voit tout, un utilisateur ne voit que les siennes
            $userId = $user->isLoanManager() ? 0 : $user->person->Id ?? 0;
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

            // Force l'utilisateur courant pour une réservation standard
            $user = $this->application->getConnectedUser();
            if (!$user->isLoanManager()) {
                $data['userId'] = $user->person->Id ?? 0;
            }

            // Contrôle disponibilité
            $available = $this->loanDataHelper->getAvailableQtyForReservation(
                WebApp::toInt($data['itemId']),
                WebApp::toStr($data['reservationDate']),
                WebApp::toStr($data['startTime']),
                WebApp::toStr($data['endTime']),
                empty($data['id']) ? null : WebApp::toInt($data['id'])
            );
            if ($available < WebApp::toInt($data['quantity'])) {
                $this->renderJsonError(
                    'Requested quantity exceeds available stock.',
                    409,
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
            $userId = $user->isLoanManager() ? 0 : $user->person->Id ?? 0;

            $ok = $this->loanDataHelper->cancelReservation($id, $userId);
            if (!$ok) {
                $this->renderJsonBadRequest("Reservation {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk([]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CALENDRIER & DISPONIBILITÉ
    // ══════════════════════════════════════════════════════════════════════════

    public function getCalendarEvents(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            $start = WebApp::toStr($_GET['start'] ?? date('Y-m-01'));
            $end   = WebApp::toStr($_GET['end']   ?? date('Y-m-t'));
            $this->renderJsonOk(
                $this->loanDataHelper->getCalendarEvents($start, $end)
            );
        }
    }

    public function getAvailability(int $itemId): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            $type  = WebApp::toStr($_GET['type'] ?? 'loan');
            $date  = WebApp::toStr($_GET['date'] ?? date('Y-m-d'));
            $start = WebApp::toStr($_GET['start'] ?? '00:00');
            $end   = WebApp::toStr($_GET['end']   ?? '23:59');

            if ($type === 'loan') {
                $due  = WebApp::toStr($_GET['dueDate'] ?? $date);
                $qty  = $this->loanDataHelper->getAvailableQtyForLoan($itemId, $date, $due);
            } else {
                $qty  = $this->loanDataHelper->getAvailableQtyForReservation($itemId, $date, $start, $end);
            }
            $this->renderJsonOk(['available' => $qty]);
        }
    }
}
