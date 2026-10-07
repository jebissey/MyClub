<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

final class DesignerController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function help(): void
    {
        $this->renderHelp('Help_Designer', fn($u) => $u->isDesigner(), __FILE__, __LINE__);
    }

    public function homeDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'designer';
            $connectedUser = $this->application->getConnectedUser();
            $content = ($this->t)('Designer');
            $params = [
                'isEventDesigner' => $connectedUser->isEventDesigner(),
                'isExerciseDesigner' => $connectedUser->isExerciseDesigner(),
                'isHomeDesigner' => $connectedUser->isHomeDesigner(),
                'isKanbanDesigner' => $connectedUser->isKanbanDesigner(),
                'isLoanDesigner' => $connectedUser->isLoanDesigner(),
                'isMenuDesigner' => $connectedUser->isMenuDesigner(),
            ];
            $compiledContent = WebApp::getcompiledContent($content, $params);

            $this->render('Designer/views/designer.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
                'content' => $compiledContent,
                'btn_HistoryBack' => true,
                'btn_Parent'      => "/admin",
            ]));
        }
    }
}
