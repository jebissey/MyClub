<?php

declare(strict_types=1);

namespace app\modules\Loan;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\models\LoanDataHelper;
use app\modules\Common\AbstractController;

class LoanController extends AbstractController
{
    public function __construct(
        Application $application,
        private LoanDataHelper $loanDataHelper,
    ) {
        parent::__construct($application);
    }

    public function calendar(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $this->render('Loan/views/calendar.latte', $this->getAllParams([
            'translations' => $this->translations_(),
            'page' => $this->application->getConnectedUser()->getPage(),
            'activeTab' => 'calendar',
            'btn_Parent' => "/user",
            'btn_HistoryBack' => true,
        ]));
    }

    public function designer(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $this->loanDataHelper->updateOverdueLoans();

        $this->render('Loan/views/designer.latte', $this->getAllParams([
            'items'        => $this->loanDataHelper->getAllItems(),
            'translations' => $this->translations_(),
            'page' => $this->application->getConnectedUser()->getPage(),
            'activeTab' => 'designer',
            'btn_Parent' => "/admin",
            'btn_HistoryBack' => true,
        ]));
    }

    public function designerHelp(): void
    {
        if (!($this->application->getConnectedUser()->isLoanDesigner() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        $lang = TranslationManager::getCurrentLanguage();
        $this->render('Common/views/info.latte', $this->getAllParams([
            'content' => $this->dataHelper->get('Languages', ['Name' => 'Help_LoanDesigner'], $lang)->$lang ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->isRedactor() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true,
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function manager(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanManager(), __FILE__, __LINE__)) {
            return;
        }

        $this->loanDataHelper->updateOverdueLoans();

        $this->render('Loan/views/manager.latte', $this->getAllParams([
            'loans'        => $this->loanDataHelper->getAllLoans(),
            'loanItems'    => $this->loanDataHelper->getActiveItems('loan'),
            'persons'      => $this->loanDataHelper->getAllPersons(),
            'translations' => $this->translations_(),
            'activeTab' => 'manager',
            'btn_Parent' => "/user",
            'btn_HistoryBack' => true,
        ]));
    }

    public function reservations(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $user   = $this->application->getConnectedUser();
        $userId = $user->isLoanManager() ? 0 : $user->person->Id;

        $this->render('Loan/views/user.latte', $this->getAllParams([
            'reservations'      => $this->loanDataHelper->getAllReservations($userId),
            'reservationItems'  => $this->loanDataHelper->getActiveItems('reservation'),
            'persons'           => $this->loanDataHelper->getAllPersons(),
            'isManager'         => $user->isLoanManager(),
            'currentUserId'     => $userId,
            'translations'      => $this->translations_(),
            'page' => $this->application->getConnectedUser()->getPage(),
            'activeTab' => 'user',
            'btn_Parent' => "/user",
            'btn_HistoryBack' => true,
        ]));
    }

    #region Private methods
    private function translations_(): array
    {
        $keys = [
            'nav.designer',
            'nav.manager',
            'nav.user',
            'nav.calendar',
            'item.title',
            'item.add',
            'item.edit',
            'item.name',
            'item.description',
            'item.type',
            'item.type.loan',
            'item.type.reservation',
            'item.type.both',
            'item.quantity',
            'item.active',
            'item.delete_confirm',
            'item.no_items',
            'record.title',
            'record.add',
            'record.item',
            'record.borrower',
            'record.lender',
            'record.loan_date',
            'record.due_date',
            'record.return_date',
            'record.returned_to',
            'record.quantity',
            'record.notes',
            'record.status',
            'record.status.active',
            'record.status.returned',
            'record.status.overdue',
            'record.status.cancelled',
            'record.return_action',
            'record.no_records',
            'reservation.title',
            'reservation.add',
            'reservation.item',
            'reservation.date',
            'reservation.start',
            'reservation.end',
            'reservation.quantity',
            'reservation.notes',
            'reservation.status',
            'reservation.status.active',
            'reservation.status.cancelled',
            'reservation.cancel_confirm',
            'reservation.no_reservations',
            'calendar.title',
            'calendar.loans',
            'calendar.reservations',
            'msg.saved',
            'msg.deleted',
            'msg.returned',
            'msg.cancelled',
            'msg.error',
            'msg.qty_exceeded',
        ];
        return $this->translations($keys, 'loan.');
    }
}
