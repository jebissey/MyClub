<?php

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class MaintenanceController extends AbstractController
{
    private const MAINTENANCE_UNSET = '/maintenance/unset';

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function checkIfSiteIsUnderMaintenance(): void
    {
error_log("\n\n" . json_encode('---###---', JSON_PRETTY_PRINT) . "\n");
        if (strpos($_SERVER['REQUEST_URI'] ?? '', self::MAINTENANCE_UNSET) !== false) return;

        $siteUnderMaintenance = $this->dataHelper->get('Metadata', ['Id' => 1], 'SiteUnderMaintenance')->SiteUnderMaintenance;
        if ($siteUnderMaintenance == 0) return;

        $this->application->getErrorManager()->raise(
            ApplicationError::ServiceUnavailable,
            "Maintenance",
            30000,
            false,
            $this->connectedUser->get()->isWebmaster() ?? false
        );
    }

    public function maintenance(): void
    {
        if (!($this->connectedUser->get()->isWebmaster() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Webmaster/views/maintenance.latte', Params::getAll(['isMyclubWebSite' => WebApp::isMyClubWebSite()]));
    }

    public function setSiteOnline(): void
    {
        if (!($this->connectedUser->get()->isWebmaster() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->dataHelper->set('Metadata', ['SiteUnderMaintenance' => 0], ['Id' => 1]);
        $this->redirect('/');
    }

    public function setSiteUnderMaintenance(): void
    {
        if (!($this->connectedUser->get()->isWebmaster() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->dataHelper->set('Metadata', ['SiteUnderMaintenance' => 1], ['Id' => 1]);
        $this->redirect('/');
    }
}
