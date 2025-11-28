<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\FFAScraper;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class FFAController extends AbstractController
{
    public function __construct(
        Application $application,
    ) {
        parent::__construct($application);
    }

    public function searchMember()
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser()->person;
        $schema = [
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
            'question' => FilterInputRule::HtmlSafeName->value,
            'year' => FilterInputRule::Int->value,
            'club' => FilterInputRule::HtmlSafeName->value,
            'page' => $this->application->getConnectedUser()->getPage(),
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $firstName = $input['firstName'] ?? $person->FirstName ?? '???';
        $lastName = $input['lastName'] ?? $person->LastName ?? '???';
        $question = $input['question'] ?? 'rank';
        $year = $input['year'] ?? date('Y');
        $club = $input['club'] ?? $this->dataHelper->get('Settings', ['Name' => 'FFA_club'], 'Value')->Value ?? '???';
        $results = [];
        $ffaScraper = new FFAScraper();
        if ($question == 'rank') $results = $ffaScraper->searchAthleteRank($firstName, $lastName, $year, $club);
        else                    $results = $ffaScraper->searchAthleteResults($firstName, $lastName, $year, $club);

        $this->render('User/views/ffaSearch.latte', $this->getAllParams([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'question' => $question,
            'results' => $results,
            'navItems' => $this->getNavItems($person),
            'question' => $question,
            'year' => $year,
            'club' => $club,
            'page' => $this->application->getConnectedUser()->getPage()
        ]));
    }
}
