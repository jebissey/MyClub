<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ErrorManager;
use app\modules\Common\AbstractController;

class MaintenanceController extends AbstractController
{
    private const MAINTENANCE_UNSET = '/maintenance/unset';

    public function __construct(
        Application $application,
        protected ErrorManager $errorManager
    ) {
        parent::__construct($application);
    }

    public function checkIfSiteIsUnderMaintenance(): void
    {
error_log("\n\n" . json_encode('---###---', JSON_PRETTY_PRINT) . "\n");
        if (strpos($_SERVER['REQUEST_URI'] ?? '', self::MAINTENANCE_UNSET) !== false) return;

        $siteUnderMaintenance = $this->dataHelper->get('Metadata', ['Id' => 1], 'SiteUnderMaintenance')->SiteUnderMaintenance;
        if ($siteUnderMaintenance == 0) return;

        $this->errorManager->raise(
            ApplicationError::ServiceUnavailable,
            "Maintenance",
            30000,
            false,
            $this->application->getConnectedUser()->isWebmaster() ?? false
        );
    }

    public function maintenance(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isWebmaster(), __FILE__, __LINE__)) {
            $this->render('Webmaster/views/maintenance.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
                'btn_HistoryBack' => true,
                'btn_Parent' => '/webmaster',
            ]));
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
