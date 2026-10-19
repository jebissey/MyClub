<?php

declare(strict_types=1);

namespace app\modules\Games;

use app\helpers\Application;
use app\modules\Common\AbstractController;
use app\modules\Games\viewModels\GameManagerViewModel;

final class GameManagerController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function help(): void
    {
        $this->renderHelp('Help_Game', fn($u) => $u->isConnected(), __FILE__, __LINE__);
    }

    public function game(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAdmin(), __FILE__, __LINE__)) {
            return;
        }
        $_SESSION['navbar'] = 'game';

        $viewModel = new GameManagerViewModel(
            content: ($this->t)('Games'),
            layoutParams: $this->getAllParams([]),
        );
        $this->render('Games/views/gameManager.latte', $viewModel->toArray());
    }
}
