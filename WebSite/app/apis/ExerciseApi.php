<?php

declare(strict_types=1);

namespace app\apis;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\models\DataHelper;
use app\models\PersonDataHelper;
use app\valueObjects\ExerciseRow;

class ExerciseApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function get(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }
        $row = $this->dataHelper->get('Exercise', ['Id' =>  $id]);
        if (!$row) {
            $this->renderJsonBadRequest("Exercise {$id} not found", __FILE__, __LINE__);
            return;
        }
        /** @var object{Id: int|string, Content: string, Title: string, LastUpdate: string} $row */
        $exercise = ExerciseRow::fromStdClass($row);
        $exercises = json_decode($exercise->Content, true) ?? [];
        $this->renderJsonOk(['exercises' => $exercises, 'title' => $exercise->Title]);
    }

    public function save(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isExerciseDesigner(), __FILE__, __LINE__)) {
            $data = $this->getJsonInput();
            if (!isset($data['exercises']) || !is_array($data['exercises'])) {
                $this->renderJsonBadRequest('Invalid exercises payload', __FILE__, __LINE__);
                return;
            }
            $this->dataHelper->set('Exercise', [
                'Content'    => json_encode($data['exercises'], JSON_UNESCAPED_UNICODE),
                'Title'      => $data['title'] ?? '??',
                'LastUpdate' => date('Y-m-d H:i:s'),
            ], ['Id' => $id]);

            $this->renderJsonOk(['id' => $id]);
        }
    }

    public function delete(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isExerciseDesigner(), __FILE__, __LINE__)) {
            return;
        }
        $data = $this->getJsonInput();
        $index = To::int($data['index'] ?? -1, -1);

        $row = $this->dataHelper->get('Exercise', ['Id' => $id]);
        if (!$row) {
            $this->renderJsonBadRequest("Exercise {$id} not found", __FILE__, __LINE__);
            return;
        }
        /** @var object{Id: int|string, Content: string, Title: string, LastUpdate: string} $row */
        $exercise = ExerciseRow::fromStdClass($row);

        /** @var array<mixed> $exercises */
        $exercises = json_decode($exercise->Content, true) ?? [];
        if ($index < 0 || $index >= count($exercises)) {
            $this->renderJsonBadRequest("Index {$index} out of range", __FILE__, __LINE__);
            return;
        }

        array_splice($exercises, $index, 1);

        $this->dataHelper->set('Exercise', [
            'Content'    => json_encode(array_values($exercises), JSON_UNESCAPED_UNICODE),
            'LastUpdate' => date('Y-m-d H:i:s'),
        ], ['Id' => $id]);

        $this->renderJsonOk([]);
    }
}
