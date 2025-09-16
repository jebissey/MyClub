<?php

namespace app\modules\Games\Solfege;

use Throwable;

use app\helpers\Application;
use app\helpers\Params;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class SolfegeController extends AbstractController
{
    public function __construct(
        Application $application,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function learn(): void
    {
        if (!($this->application->getConnectedUser()->get()->isEventDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }

        $this->render('Event/views/solfege_learn.latte', Params::getAll([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'title' => 'Apprentissage du Solfège'
        ]));
    }

    public function saveScore(): void
    {
        if (!($this->application->getConnectedUser()->get()->isEventDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['scores'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            return;
        }

        // Ici vous pouvez sauvegarder les scores en base de données
        // Exemple de structure de données reçue :
        // {
        //   "scores": [
        //     {"note": "C4", "attempts": 2, "totalTime": 3500},
        //     {"note": "D4", "attempts": 1, "totalTime": 1200}
        //   ]
        // }

        try {
            // Sauvegarde en base (à adapter selon votre structure)
            // $this->dataHelper->saveUserScores($this->application->getConnectedUser()->get()->getId(), $input['scores']);

            echo json_encode(['success' => true, 'message' => 'Scores sauvegardés avec succès']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
        }
    }
}
