<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\FFAScraper;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

final class FFAController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function searchMember(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();
        $person = $connectedUser->person;
        $schema = [
            'firstName' => FilterInputRule::PersonName->value,
            'lastName'  => FilterInputRule::PersonName->value,
            'question'  => FilterInputRule::HtmlSafeName->value,
            'year'      => FilterInputRule::Int->value,
            'club'      => FilterInputRule::HtmlSafeName->value,
            'page'      => $connectedUser->getPage() ?? '',
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());

        /** @var string $firstName */
        $firstName = $input['firstName'] ?? $person->FirstName ?? '???';
        /** @var string $lastName */
        $lastName = $input['lastName'] ?? $person->LastName ?? '???';
        $question = $input['question'] ?? 'rank';
        /** @var string $year */
        $year = $input['year'] ?? date('Y');
        $club = $input['club'] ?? $this->dataHelper->getSetting('FFA_club', '???');

        $ffaScraper = new FFAScraper();
        $results = $question === 'rank'
            ? $ffaScraper->searchAthleteRank($firstName, $lastName, $year, To::str($club))
            : $ffaScraper->searchAthleteResults($firstName, $lastName, $year, To::str($club));

        $this->render('User/views/ffaSearch.latte', $this->getAllParams([
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'question'  => $question,
            'results'   => $results,
            'navItems'  => $this->getNavItems($person),
            'year'      => $year,
            'club'      => $club,
            'page'      => $connectedUser->getPage(),
        ]));
    }
}
