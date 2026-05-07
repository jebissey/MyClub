<?php
declare(strict_types=1);
namespace app\apis;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\ArticleDataHelper;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class ExerciseApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private ArticleDataHelper $articleDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    /** Retourne les exercices d'un article */
    public function get(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected())) {
            return;
        }
        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if (!$article) {
            $this->renderJsonBadRequest("Article {$id} not found", __FILE__, __LINE__);
            return;
        }
        $exercises = json_decode($article->Content ?? '[]', true) ?? [];
        $this->renderJsonOk(['exercises' => $exercises, 'title' => $article->Title]);
    }

    /** Sauvegarde le tableau complet d'exercices */
    public function save(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isExerciseDesigner())) {
            return;
        }
        $data = $this->getJsonInput();

        if (!isset($data['exercises']) || !is_array($data['exercises'])) {
            $this->renderJsonBadRequest('Invalid exercises payload', __FILE__, __LINE__);
            return;
        }

        $this->dataHelper->set('Article', [
            'Content'    => json_encode($data['exercises'], JSON_UNESCAPED_UNICODE),
            'Title'      => $data['title'] ?? 'Exercices',
            'LastUpdate' => date('Y-m-d H:i:s'),
        ], ['Id' => $id]);

        $this->renderJsonOk(['id' => $id]);
    }

    /** Supprime un exercice par index dans le tableau JSON */
    public function delete(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isExerciseDesigner())) {
            return;
        }
        $data = $this->getJsonInput();
        $index = (int)($data['index'] ?? -1);

        $article = $this->articleDataHelper->getLatestArticle([$id]);
        if (!$article) {
            $this->renderJsonBadRequest("Article {$id} not found", __FILE__, __LINE__);
            return;
        }

        $exercises = json_decode($article->Content ?? '[]', true) ?? [];
        if ($index < 0 || $index >= count($exercises)) {
            $this->renderJsonBadRequest("Index {$index} out of range", __FILE__, __LINE__);
            return;
        }

        array_splice($exercises, $index, 1);

        $this->dataHelper->set('Article', [
            'Content'    => json_encode(array_values($exercises), JSON_UNESCAPED_UNICODE),
            'LastUpdate' => date('Y-m-d H:i:s'),
        ], ['Id' => $id]);

        $this->renderJsonOk([]);
    }
}