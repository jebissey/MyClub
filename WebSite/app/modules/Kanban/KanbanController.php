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
        $selectedProjectId = $this->flight->request()->query->getData()["p"] ?? null;
        if ($selectedProjectId !== null) {
            $selectedProjectId = (int) $selectedProjectId;
            if (!$this->kanbanDataHelper->userHasAccessToProject($personId, $selectedProjectId)) {
                $selectedProjectId = null;
            }
        }

        $this->render('Kanban/views/kanban.latte', $this->getAllParams([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'title' => 'Kanban Board',
            'page' => $this->application->getConnectedUser()->getPage(),
            'personId' => $personId,
            'projects' => $this->kanbanDataHelper->getKanbanProjects($personId),
            'columns' => [
                ['icon' => '💡', 'label' => 'Backlog'],
                ['icon' => '☑️', 'label' => 'Selected'],
                ['icon' => '🔧', 'label' => 'In Progress'],
                ['icon' => '🏁', 'label' => 'Done']
            ],
            'selectedProjectId' => $selectedProjectId
        ]));
    }
}
