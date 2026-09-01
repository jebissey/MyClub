<?php

declare(strict_types=1);

namespace app\modules\Loan;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\models\LoanDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\Loan\viewModels\LoanCalendarViewModel;
use app\modules\Loan\viewModels\LoanDesignerViewModel;
use app\modules\Loan\viewModels\LoanManagerViewModel;
use app\modules\Loan\viewModels\LoanUserViewModel;

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

        $viewModel = new LoanCalendarViewModel(
            i18n: $this->doTranslations(),
            activeTab: 'calendar',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Loan/views/calendar.latte', $viewModel->toArray());
    }

    public function designer(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $this->loanDataHelper->updateOverdueLoans();

        $viewModel = new LoanDesignerViewModel(
            items: array_values($this->loanDataHelper->getAllItems()),
            i18n: $this->doTranslations(),
            activeTab: 'designer',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Loan/views/designer.latte', $viewModel->toArray());
    }

    public function designerHelp(): void
    {
        if (!$this->application->getConnectedUser()->isLoanDesigner()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        $lang = TranslationManager::getCurrentLanguage();
        $helpRow = $this->dataHelper->get('Languages', ['Name' => 'Help_LoanDesigner'], $lang);
        $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

        $viewModel = new InfoViewModel(
            content: $content,
            timer: 0,
            hasAuthorization: $this->application->getConnectedUser()->isRedactor(),
            previousPage: true,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Common/views/info.latte', $viewModel->toArray());
    }

    public function manager(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanManager(), __FILE__, __LINE__)) {
            return;
        }

        $this->loanDataHelper->updateOverdueLoans();

        $viewModel = new LoanManagerViewModel(
            loans: array_values($this->loanDataHelper->getAllLoans()),
            loanItems: array_values($this->loanDataHelper->getActiveItems('loan')),
            persons: array_values($this->loanDataHelper->getAllPersons()),
            i18n: $this->doTranslations(),
            activeTab: 'manager',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Loan/views/manager.latte', $viewModel->toArray());
    }

    public function reservations(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $user   = $this->application->getConnectedUser();
        $userId = $user->isLoanManager() ? 0 : $user->person->Id ?? 0;

        $viewModel = new LoanUserViewModel(
            reservations: array_values($this->loanDataHelper->getAllReservations($userId)),
            reservationItems: array_values($this->loanDataHelper->getActiveItems('reservation')),
            persons: array_values($this->loanDataHelper->getAllPersons()),
            isManager: $user->isLoanManager(),
            currentUserId: $userId,
            i18n: $this->doTranslations(),
            activeTab: 'user',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Loan/views/user.latte', $viewModel->toArray());
    }

    #region Private methods
    /**
     * @return array<string, string>
     */
    private function doTranslations(): array
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
