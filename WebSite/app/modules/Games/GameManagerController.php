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
        $this->renderHelp('Help_Game', fn($u) => $u->isAnybody(), __FILE__, __LINE__);
    }

    public function games(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }
        $_SESSION['navbar'] = 'games';

        $viewModel = new GameManagerViewModel(
            content: ($this->t)('Games'),
            layoutParams: $this->getAllParams([]),
        );
        $this->render('Games/views/gameManager.latte', $viewModel->toArray());
    }
}
