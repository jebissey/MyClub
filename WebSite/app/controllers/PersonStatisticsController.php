<?php

namespace app\controllers;

use app\helpers\PersonStatistics;

class PersonStatisticsController extends BaseController
{
    public function showStatistics()
    {
        if ($person = $this->getPerson([])) {

            $personalStatistics = new PersonStatistics($this->pdo);
            $season = $personalStatistics->getSeasonRange();
            echo $this->latte->render('app/views/user/statistics.latte', $this->params->getAll([
                'stats' => $personalStatistics->getStats($person, $season['start'], $season['end']),
                'seasons' => $personalStatistics->getAvailableSeasons(),
                'currentSeason' => $season,
                'navItems' => $this->getNavItems(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
