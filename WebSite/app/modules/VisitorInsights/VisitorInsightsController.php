<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights;

use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class VisitorInsightsController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function helpVisitorInsights(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_visitorInsights'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isVisitorInsights() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage()
            ]);
        }
    }

    public function visitorInsights(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isVisitorInsights())) {
            $_SESSION['navbar'] = 'visitorInsights';
            $this->render('Webmaster/views/visitorInsights.latte', Params::getAll([
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }
}
