<?php

namespace app\controllers;

use app\helpers\FFAScraper;

class FFAController extends BaseController
{
    /**
     * Affiche les informations FFA d'un membre
     */
    public function showFFAInfo()
    {
        if ($person = $this->getPerson([])) {
            $ffaScraper = new FFAScraper();
            $athleteInfo = $ffaScraper->searchAthlete($person['FirstName'], $person['LastName']);
            
            echo $this->latte->render('app/views/user/ffaInfo.latte', $this->params->getAll([
                'person' => $person,
                'athleteInfo' => $athleteInfo
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
    
    /**
     * Recherche manuelle d'un membre dans la base FFA
     */
    public function searchMember()
    {
        if ($person = $this->getPerson([])) {
            $firstName = $person['FirstName'] ?? '';
            $lastName = $person['LastName'] ?? '';
            $results = [];
            
            if ($firstName && $lastName) {
                $ffaScraper = new FFAScraper();
                $results = $ffaScraper->searchAthlete($firstName, $lastName);
            }
            
            echo $this->latte->render('app/views/user/ffaSearch.latte', $this->params->getAll([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'results' => $results
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}