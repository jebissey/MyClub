<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class DesignerController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function helpDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner())) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_designer'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isDesigner() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage()
            ]);
        }
    }

    public function homeDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner())) {
            $_SESSION['navbar'] = 'designer';
            $connectedUser = $this->application->getConnectedUser();
            $content = $this->languagesDataHelper->translate('Designer');
            $params = [
                'isEventDesigner' => $connectedUser->isEventDesigner(),
                'isHomeDesigner' => $connectedUser->isHomeDesigner(),
                'isKanbanDesigner' => $connectedUser->isKanbanDesigner(),
                'isNavbarDesigner' => $connectedUser->isNavbarDesigner(),
            ];
            $compiledContent = WebApp::getcompiledContent($content, $params);

            $this->render('Designer/views/designer.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
                'content' => $compiledContent
            ]));
        }
    }
}
