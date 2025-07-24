<?php

namespace app\controllers;

use app\helpers\FFAScraper;

class FFAController extends BaseController
{
    public function searchMember()
    {
        if ($person = $this->personDataHelper->getPerson([])) {
            $firstName = $_GET['firstName'] ?? $person->FirstName ?? '';
            $lastName = $_GET['lastName'] ?? $person->LastName ?? '';
            $question = $_GET['question'] ?? 'rank';
            $year = $_GET['year'] ?? date('Y');
            $club = $_GET['club'] ?? $this->application->getSettings()->get('FFA_club')?? '';
            $results = [];
            $ffaScraper = new FFAScraper();
            if($question == 'rank') $results = $ffaScraper->searchAthleteRank($firstName, $lastName, $year, $club);
            else                    $results = $ffaScraper->searchAthleteResults($firstName, $lastName, $year, $club);

            $this->render('app/views/user/ffaSearch.latte', $this->params->getAll([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'question' => $question,
                'results' => $results,
                'navItems' => $this->getNavItems($person),
                'question' => $question,
                'year' => $year,
                'club' => $club,
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
