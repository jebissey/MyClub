<?php

declare(strict_types=1);

namespace app\modules\Kanban;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\KanbanDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\Kanban\viewModels\KanbanBoardViewModel;

class KanbanController extends AbstractController
{
    public function __construct(
        Application $application,
        private KanbanDataHelper $kanbanDataHelper,
    ) {
        parent::__construct($application);
    }

    public function board(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isKanbanDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $personId = $this->application->getConnectedUser()->person->Id ?? 0;
        $query    = $this->flight->request()->query->getData();

        $selectedProjectId = null;
        if (isset($query['p']) && is_scalar($query['p'])) {
            $selectedProjectId = (int) $query['p'];
        }

        $isOwner = null;
        if ($selectedProjectId !== null) {
            $isOwner = $this->kanbanDataHelper->userHasAccessToProject($personId, $selectedProjectId);
        }

        $schema = [
            'ct'     => FilterInputRule::Int->value,
            'title'  => FilterInputRule::Content->value,
            'detail' => FilterInputRule::Content->value,
        ];
        $filters = WebApp::filterInput($schema, $this->flight->request()->query->getData());

        $viewModel = new KanbanBoardViewModel(
            personId: $personId,
            projects: array_values($this->kanbanDataHelper->getKanbanProjects()),
            columns: [
                ['icon' => '💡', 'label' => 'Backlog'],
                ['icon' => '☑️', 'label' => 'Selected'],
                ['icon' => '🔧', 'label' => 'In Progress'],
                ['icon' => '🏁', 'label' => 'Done'],
            ],
            selectedProjectId: $selectedProjectId,
            cardTypes: array_values($this->kanbanDataHelper->getProjectCardTypes($selectedProjectId)),
            filters: $filters,
            isOwner: $isOwner,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Kanban/views/kanban.latte', $viewModel->toArray());
    }

    public function help(): void
    {
        if (!$this->application->getConnectedUser()->isKanbanDesigner()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $lang    = TranslationManager::getCurrentLanguage();
        $helpRow = $this->dataHelper->get('Languages', ['Name' => 'Help_KanbanDesigner'], $lang);
        $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

        $viewModel = new InfoViewModel(
            content: $content,
            timer: 0,
            hasAuthorization: $this->application->getConnectedUser()->isRedactor(),
            previousPage: true,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Common/views/info.latte', $viewModel->toArray());
    }
}
