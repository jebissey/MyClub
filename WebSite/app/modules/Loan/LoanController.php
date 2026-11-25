<?php

declare(strict_types=1);

namespace app\modules\Loan;

use app\helpers\Application;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\models\LoanItemDataHelper;
use app\models\LoanRecordDataHelper;
use app\models\LoanReservationDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\Loan\viewModels\LoanCalendarViewModel;
use app\modules\Loan\viewModels\LoanDesignerViewModel;
use app\modules\Loan\viewModels\LoanManagerViewModel;
use app\modules\Loan\viewModels\LoanUserViewModel;

final class LoanController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly LoanItemDataHelper $loanItemDataHelper,
        private readonly LoanRecordDataHelper $loanRecordDataHelper,
        private readonly LoanReservationDataHelper $loanReservationDataHelper,
        private readonly PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application);
    }

    public function calendar(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $viewModel = new LoanCalendarViewModel(
            activeTab: 'calendar',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Loan/views/calendar.latte', $viewModel);
    }

    public function designer(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $this->loanRecordDataHelper->updateOverdueLoans();

        $viewModel = new LoanDesignerViewModel(
            items: $this->loanItemDataHelper->getAllItems(),
            activeTab: 'designer',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Loan/views/designer.latte', $viewModel);
    }

    public function designerHelp(): void
    {
        if (!$this->application->getConnectedUser()->isLoanDesigner()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        $lang = TranslationManager::getCurrentLanguage();
        $helpRow = $this->dataHelper->get('Languages', ['Name' => 'Help_LoanDesigner'], $lang);
        $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

        $viewModel = new InfoViewModel(
            content: To::str($content),
            timer: 0,
            previousPage: true,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Common/views/info.latte', $viewModel);
    }

    public function manager(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isLoanManager(), __FILE__, __LINE__)) {
            return;
        }

        $this->loanRecordDataHelper->updateOverdueLoans();

        $viewModel = new LoanManagerViewModel(
            loans: $this->loanRecordDataHelper->getAllLoans(),
            loanItems: $this->loanItemDataHelper->getActiveItems('loan'),
            persons: $this->personDataHelper->getMemberOptions(),
            activeTab: 'manager',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Loan/views/manager.latte', $viewModel);
    }

    public function reservations(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $user   = $this->application->getConnectedUser();
        $userId = $user->isLoanManager() ? 0 : $user->person->id ?? 0;

        $viewModel = new LoanUserViewModel(
            reservations: $this->loanReservationDataHelper->getAllReservations($userId),
            reservationItems: $this->loanItemDataHelper->getActiveItems('reservation'),
            persons: $this->personDataHelper->getMemberOptions(),
            isManager: $user->isLoanManager(),
            currentUserId: $userId,
            activeTab: 'user',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Loan/views/user.latte', $viewModel);
    }
}
