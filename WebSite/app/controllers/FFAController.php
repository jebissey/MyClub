<?php

namespace app\controllers;

use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\FFAScraper;
use app\helpers\Params;

class FFAController extends BaseController
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function searchMember()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $firstName = $_GET['firstName'] ?? $person->FirstName ?? '';
            $lastName = $_GET['lastName'] ?? $person->LastName ?? '';
            $question = $_GET['question'] ?? 'rank';
            $year = $_GET['year'] ?? date('Y');
            $club = $_GET['club'] ?? $this->dataHelper->get('Settings', ['Name' => 'FFA_club']) ?? '';
            $results = [];
            $ffaScraper = new FFAScraper();
            if ($question == 'rank') $results = $ffaScraper->searchAthleteRank($firstName, $lastName, $year, $club);
            else                    $results = $ffaScraper->searchAthleteResults($firstName, $lastName, $year, $club);

            $this->render('app/views/user/ffaSearch.latte', Params::getAll([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'question' => $question,
                'results' => $results,
                'navItems' => $this->getNavItems($person),
                'question' => $question,
                'year' => $year,
                'club' => $club,
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
