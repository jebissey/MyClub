<?php

namespace app\controllers;

use app\helpers\FFAScraper;

class FFAController extends BaseController
{

    public function searchMember()
    {
        if ($person = $this->getPerson([])) {
            $firstName = $_GET['firstName'] ?? $person['FirstName'] ?? '';
            $lastName = $_GET['lastName'] ?? $person['LastName'] ?? '';
            $results = [];
            $ffaScraper = new FFAScraper();
            $results = $ffaScraper->searchAthlete($firstName, $lastName);

            echo $this->latte->render('app/views/user/ffaSearch.latte', $this->params->getAll([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'results' => $results,
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
