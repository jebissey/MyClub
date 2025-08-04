<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\enums\InputPattern;
use app\helpers\Application;
use app\helpers\FFAScraper;
use app\helpers\Params;
use app\helpers\WebApp;

class FFAController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function searchMember()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $schema = [
                'firstName' => InputPattern::PersonName->value,
                'lastName' => InputPattern::PersonName->value,
                'question' => InputPattern::Content->value,
                'year' => 'int',
                'club' => InputPattern::Content->value,
            ];
            $input = WebApp::filterInput($schema, $_GET);
            $firstName = $input['firstName'] !== '' ? $input['firstName'] : ($person->FirstName ?? '');
            $lastName = $input['lastName'] !== '' ? $input['lastName'] : $person->LastName ?? '';
            $question = $input['question'] !== '' ? $input['question'] : 'rank';
            $year = $input['year'] !== '' ? $input['year'] : date('Y');
            $club = $input['club'] !== '' ? $input['club'] : $this->dataHelper->get('Settings', ['Name' => 'FFA_club']) ?? '';
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
