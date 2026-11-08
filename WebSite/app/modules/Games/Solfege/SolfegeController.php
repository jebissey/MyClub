<?php

declare(strict_types=1);

namespace app\modules\Games\Solfege;

use app\helpers\Application;
use app\modules\Common\AbstractController;
use app\modules\Games\Solfege\viewModels\SolfegeViewModel;

final class SolfegeController extends AbstractController
{
    /**
     * Keys of the virtual piano: note code, letter used to find the translated syllable, black key or not.
     *
     * @var list<array{note: string, letter: string, black: bool}>
     */
    private const PIANO_KEYS = [
        ['note' => 'C4',  'letter' => 'c', 'black' => false],
        ['note' => 'C#4', 'letter' => 'c', 'black' => true],
        ['note' => 'D4',  'letter' => 'd', 'black' => false],
        ['note' => 'D#4', 'letter' => 'd', 'black' => true],
        ['note' => 'E4',  'letter' => 'e', 'black' => false],
        ['note' => 'F4',  'letter' => 'f', 'black' => false],
        ['note' => 'F#4', 'letter' => 'f', 'black' => true],
        ['note' => 'G4',  'letter' => 'g', 'black' => false],
        ['note' => 'G#4', 'letter' => 'g', 'black' => true],
        ['note' => 'A4',  'letter' => 'a', 'black' => false],
        ['note' => 'A#4', 'letter' => 'a', 'black' => true],
        ['note' => 'B4',  'letter' => 'b', 'black' => false],
    ];

    /**
     * Key used by the template and the JavaScript => translation key.
     *
     * @var array<string, string>
     */
    private const I18N_KEYS = [
        'staff'        => 'solfege.staff',
        'statistics'   => 'solfege.statistics',
        'currentNote'  => 'solfege.current_note',
        'attempts'     => 'solfege.attempts',
        'time'         => 'solfege.time',
        'totalScore'   => 'solfege.total_score',
        'nextNote'     => 'solfege.next_note',
        'restart'      => 'solfege.restart',
        'piano'        => 'solfege.piano',
        'correct'      => 'solfege.js.correct',
        'tryAgain'     => 'solfege.js.try_again',
        'reset'        => 'solfege.js.reset',
        'scoresSaved'  => 'solfege.js.scores_saved',
    ];

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function learn(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if (!$connectedUser->isConnected()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $pianoKeys = [];
        $noteNames = [];
        foreach (self::PIANO_KEYS as $key) {
            $syllable = $this->translate('solfege.note.' . $key['letter']);
            $name = $key['black'] ? $syllable . '#' : $syllable;
            $pianoKeys[] = ['note' => $key['note'], 'name' => $name, 'black' => $key['black']];
            $noteNames[$key['note']] = $name;
        }

        $viewModel = new SolfegeViewModel(
            title: $this->translate('solfege.title'),
            navItems: $this->getNavItems($connectedUser->person?->email),
            pianoKeys: $pianoKeys,
            noteNames: $noteNames,
            i18n: array_map(fn(string $key): string => $this->translate($key), self::I18N_KEYS),
            layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
        );

        $this->views->render('Games/Solfege/views/solfege_learn.latte', $viewModel);
    }

    public function saveScore(): void
    {
        if (!$this->application->getConnectedUser()->isConnected()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');

        if ($json === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Unable to read input']);
            return;
        }

        $input = json_decode($json, true);

        if (!is_array($input) || !isset($input['scores'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            return;
        }

        // TODO: persist the scores. Expected payload:
        // {"scores": [{"note": "C4", "attempts": 2, "totalTime": 3500}, ...]}

        echo json_encode(['success' => true]);
    }
}
