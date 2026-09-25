<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ErrorManager;
use app\modules\Common\AbstractController;
use app\modules\Webmaster\viewModels\MaintenanceViewModel;

final class MaintenanceController extends AbstractController
{

    public function __construct(
        Application $application,
        protected ErrorManager $errorManager
    ) {
        parent::__construct($application);
    }

    public function maintenance(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $viewModel = new MaintenanceViewModel(
                layoutParams: $this->getAllParams([]),
            );

            $this->render('Webmaster/views/maintenance.latte', $viewModel->toArray());
        }
    }

    public function setSiteOnline(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->dataHelper->set('Metadata', ['SiteUnderMaintenance' => 0], ['Id' => 1]);
            $this->redirect('/');
        }
    }

    public function setSiteUnderMaintenance(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->dataHelper->set('Metadata', ['SiteUnderMaintenance' => 1], ['Id' => 1]);
            $this->redirect('/');
        }
    }
}
