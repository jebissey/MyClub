<?php

declare(strict_types=1);

namespace app\modules\Exercise;

use app\enums\ApplicationError;
use app\exceptions\IntegrityException;
use app\helpers\Application;
use app\models\ArticleDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\ArticleService;

class ExerciseController extends AbstractController
{
    public function __construct(
        Application $application,
        private ArticleService $articleService,
        private ArticleDataHelper $articleDataHelper,
    ) {
        parent::__construct($application);
    }

    public function create(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isExerciseDesigner())) {
            return;
        }

        $userId = $this->application->getConnectedUser()->person->Id
            ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);

        // Crée un article vierge et y stocke un tableau JSON vide
        $articleId = $this->articleService->createWithMedia($userId);
        $this->dataHelper->set('Article', ['Content' => '[]'], ['Id' => $articleId]);

        $this->redirect('/exercise/edit/' . $articleId);
    }

    public function edit(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isExerciseDesigner())) {
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if (!$article) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $exercises = json_decode($article->Content ?? '[]', true) ?? [];

        $this->render('Exercise/views/editor.latte', $this->getAllParams([
            'articleId'   => $id,
            'title'       => $article->Title ?? '',
            'exercises'   => $exercises,
            'translations' => $this->translations(),
            'btn_Parent'  => '/admin',
            'btn_HistoryBack' => true,
        ]));
    }

    public function save(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isExerciseDesigner())) {
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if (!$article || ($this->application->getConnectedUser()->person?->Id ?? 0) != $article->CreatedBy) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }

        $rawJson = $this->flight->request()->data->getData()['exercises'] ?? '[]';
        $exercises = json_decode($rawJson, true);

        if (!is_array($exercises)) {
            $_SESSION['error'] = 'JSON invalide';
            $this->redirect('/exercise/edit/' . $id);
            return;
        }

        $title = trim($this->flight->request()->data->getData()['title'] ?? '');

        $this->dataHelper->set('Article', [
            'Title'      => $title ?: 'Exercices',
            'Content'    => json_encode($exercises, JSON_UNESCAPED_UNICODE),
            'LastUpdate' => date('Y-m-d H:i:s'),
        ], ['Id' => $id]);

        $_SESSION['success'] = $this->languagesDataHelper->translate('exercise.msg.saved');
        $this->redirect('/exercise/edit/' . $id);
    }

    public function play(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected())) {
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if (!$article) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $exercises = json_decode($article->Content ?? '[]', true) ?? [];

        $this->render('Exercise/views/player.latte', $this->getAllParams([
            'articleId' => $id,
            'title'     => $article->Title ?? '',
            'exercises' => $exercises,
            'translations' => $this->translations(),
        ]));
    }
    
    #region Private functions
    private function translations(): array
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
        $trans = [];
        foreach ($keys as $k) {
            $trans[$k] = $this->languagesDataHelper->translate('exercise.' . $k);
        }
        return $trans;
    }
}
