<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\helpers\Application;
use app\modules\Common\AbstractController;
use app\modules\Designer\viewModels\DesignerHomeViewModel;

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

            $viewModel = new DesignerHomeViewModel(
                content: '',
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Designer/views/designer.latte', $viewModel);
        }
    }
}
