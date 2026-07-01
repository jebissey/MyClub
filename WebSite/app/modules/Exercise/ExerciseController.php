<?php

declare(strict_types=1);

namespace app\modules\Exercise;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ExerciseTableDataHelper;
use app\modules\Common\TableController;

class ExerciseController extends TableController
{
    public function __construct(
        Application $application,
        private ExerciseTableDataHelper $exerciseTableDataHelper,
    ) {
        parent::__construct($application);
    }

    public function create(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isExerciseDesigner(), __FILE__, __LINE__)) {
            $exerciseId = $this->dataHelper->set('Exercise', [
                'Title' => '',
                'Detail' => '',
                'Content' => '[]',
                'CreatedBy' => $this->application->getConnectedUser()->person->Id,
            ]);
            $this->redirect('/exercise/edit/' . $exerciseId);
        }
    }

    public function edit(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isExerciseDesigner(), __FILE__, __LINE__)) {
            $exercise = $this->dataHelper->get('Exercise', ['Id' => $id], 'Content, Title, CreatedBy');
            if (!$exercise) {
                $this->raiseForbidden(__FILE__, __LINE__);
                return;
            }

            $this->render('Exercise/views/editor.latte', $this->getAllParams([
                'articleId'   => $id,
                'title'       => $exercise->Title ?? '',
                'exercises'   => json_decode($exercise->Content ?? '[]', true) ?? [],
                'i18n' => $this->doTranslations(),
                'btn_Parent'  => '/exercises',
                'btn_HistoryBack' => true,
            ]));
        }
    }

    public function index(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $schema = [
            'PersonName' => FilterInputRule::PersonName->value,
            'title'      => FilterInputRule::Content->value,
            'detail'     => FilterInputRule::Content->value,
            'timestamp'  => FilterInputRule::DateTime->value,
            'lastUpdate' => FilterInputRule::DateTime->value,
            'menu'       => ['oui', 'non'],
            'GroupName'  => FilterInputRule::HtmlSafeName->value,
            'Content'    => FilterInputRule::Content->value,
            'Id'         => FilterInputRule::Int->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'PersonName', 'label' => ($this->t)('article.label.created_by')],
            ['name' => 'title', 'label' => ($this->t)('article.label.title')],
            ['name' => 'lastUpdate', 'label' => ($this->t)('article.label.last_update')],
            ['name' => 'GroupName', 'label' => ($this->t)('article.label.group')],
            ['name' => 'Content', 'label' => ($this->t)('article.label.content')],
            ['name' => 'Id', 'label' => 'ID'],
        ];
        $columns = [
            ['field' => 'PersonName', 'label' => 'Créé par'],
            ['field' => 'Title', 'label' => 'Titre'],
            ['field' => 'Detail', 'label' => 'Détails'],
            ['field' => 'LastUpdate', 'label' => 'Dernière modification'],
            ['field' => 'GroupName', 'label' => 'Groupe'],
            ['field' => 'ForMembers', 'label' => 'Club'],
        ];
        $query = $this->exerciseTableDataHelper->getQuery($connectedUser);
        $data = $this->prepareTableData($query, $filterValues);
        $this->render('Exercise/views/exercises_index.latte', $this->getAllParams([
            'exercises' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'userConnected' => $connectedUser->person ?? false,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person),
            'page' => $connectedUser->getPage(),
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/designer",
        ]));
    }

    public function save(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isExerciseDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $exercise = $this->dataHelper->get('Exercise', ['Id' => $id], 'Content, Title, CreatedBy');
        if (!$exercise || ($this->application->getConnectedUser()->person->Id ?? 0) != $exercise->CreatedBy) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }

        $rawJson = $this->flight->request()->data->getData()['exercises'] ?? '[]';
        $exercises = json_decode($rawJson, true);

        if (!is_array($exercises)) {
            $_SESSION['error'] = ($this->t)('exercise.msg.invalid_json');
            $this->redirect('/exercise/edit/' . $id);
            return;
        }

        $title = trim($this->flight->request()->data->getData()['title'] ?? '');

        $this->dataHelper->set('Exercise', [
            'Title'      => $title ?: 'Exercices',
            'Content'    => json_encode($exercises, JSON_UNESCAPED_UNICODE),
            'LastUpdate' => date('Y-m-d H:i:s'),
        ], ['Id' => $id]);

        $_SESSION['success'] = ($this->t)('exercise.msg.saved');
        $this->redirect('/exercise/edit/' . $id);
    }

    public function play(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $exercise = $this->dataHelper->get('Exercise', ['Id' => $id], 'Content, Title, CreatedBy');
        if (!$exercise) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $exercises = json_decode($exercise->Content ?? '[]', true) ?? [];

        $this->render('Exercise/views/player.latte', $this->getAllParams([
            'articleId' => $id,
            'title'     => $exercise->Title ?? '',
            'exercises' => $exercises,
            'i18n' => $this->doTranslations(),
        ]));
    }

    #region Private functions
    private function doTranslations(): array
    {
        $keys = [
            'nav.designer',
            'nav.player',
            'title',
            'add',
            'prep.title',
            'prep.text',
            'prep.image',
            'prep.sound',
            'prep.duration',
            'ex.duration',
            'save',
            'msg.saved',
            'msg.error',
        ];
        return $this->translations($keys, 'exercise.');
    }
}
