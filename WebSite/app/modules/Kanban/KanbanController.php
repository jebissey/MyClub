<?php

declare(strict_types=1);

namespace app\modules\Kanban;

use app\helpers\Application;
use app\models\KanbanDataHelper;
use app\modules\Common\AbstractController;

class KanbanController extends AbstractController
{

    public function __construct(Application $application, private KanbanDataHelper $kanbanDataHelper)
    {
        parent::__construct($application);
    }

    public function board(): void
    {
        if (!($this->application->getConnectedUser()->isKanbanDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }

        $personId = $this->application->getConnectedUser()->person->Id;

        $cards = $this->kanbanDataHelper->getKanbanCards($personId);
        $stats = $this->kanbanDataHelper->getKanbanStats($personId);

        $this->render('Kanban/views/kanban.latte', $this->getAllParams([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'title' => 'Kanban Board',
            'page' => $this->application->getConnectedUser()->getPage(),
            'cards' => $cards,
            'stats' => $stats,
            'personId' => $personId,
            'projects' => $this->kanbanDataHelper->getKanbanProjects($personId),
            'columns' => [
                ['icon' => '💡', 'label' => 'Backlog'],
                ['icon' => '☑️', 'label' => 'Selected'],
                ['icon' => '🔧', 'label' => 'In Progress'],
                ['icon' => '🏁', 'label' => 'Done']
            ]
        ]));
    }

    public function history(): void
    {
        if (!($this->application->getConnectedUser()->isKanbanDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }

        $personId = $this->application->getConnectedUser()->person->getId();
        $kanbanId = (int)($_GET['id'] ?? 0);

        // Vérifier que la carte appartient bien à l'utilisateur
        $card = $this->kanbanDataHelper->getKanbanCard($kanbanId, $personId);

        if (!$card) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }

        $history = $this->kanbanDataHelper->getKanbanHistory($kanbanId);

        $this->render('Kanban/views/kanban_history.latte', $this->getAllParams([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'title' => 'Historique - ' . $card['Title'],
            'page' => $this->application->getConnectedUser()->getPage(),
            'card' => $card,
            'history' => $history,
        ]));
    }
}
