<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\FFAScraper;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\FfaSearchViewModel;

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
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());

        $firstName = To::str($input['firstName'] ?? $person->firstName ?? '???');
        $lastName  = To::str($input['lastName'] ?? $person->lastName ?? '???');
        $question  = To::str($input['question'] ?? 'rank');
        $year      = To::str($input['year'] ?? date('Y'));
        $club      = To::str($input['club'] ?? $this->dataHelper->getSetting('FFA_club', '???'));

        $ffaScraper = new FFAScraper();
        $results = $question === 'rank'
            ? $ffaScraper->searchAthleteRank($firstName, $lastName, $year, $club)
            : $ffaScraper->searchAthleteResults($firstName, $lastName, $year, $club);

        $viewModel = new FfaSearchViewModel(
            firstName: $firstName,
            lastName: $lastName,
            question: $question,
            results: $results,
            navItems: $this->getNavItems($person?->email),
            year: $year,
            club: $club,
            layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
        );

        $this->views->render('User/views/ffaSearch.latte', $viewModel);
    }
}
