<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\DistributionCalculator;
use app\helpers\WebApp;
use app\models\LogDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\modules\Common\AbstractController;

class UserStatisticsController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonStatisticsDataHelper $personalStatisticsDataHelper,
        private LogDataHelper $logDataHelper,
        private ParticipantDataHelper $participantDataHelper,
        private DistributionCalculator $distributionCalculator,
    ) {
        parent::__construct($application);
    }

    public function showStatistics(): void
    {
        $person = $this->application->getConnectedUser()->person ?? null;
        if (!$person) {
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, '...');
            return;
        }

        $season = $this->resolveSeason();

        $this->render('User/views/user_statistics.latte', $this->getAllParams([
            'stats' => $this->personalStatisticsDataHelper->getStats(
                $person,
                $season['start'],
                $season['end'],
                $this->application->getConnectedUser()->isWebmaster()
            ),
            'seasons'                => $this->personalStatisticsDataHelper->getAvailableSeasons(),
            'currentSeason'          => $season,
            'navItems'               => $this->getNavItems($person),
            'chartData'              => $this->buildChartData($this->getVisitCounts($season), $person),
            'participationChartData' => $this->buildChartData($this->getParticipationCounts($season), $person),
            'page'                   => $this->application->getConnectedUser()->getPage(1),
            'btn_HistoryBack'        => true,
            'btn_Parent'             => "/user",
            'translations'           => [
                'visitsYAxis'          => $this->languagesDataHelper->translate('user.statistics.chart.visits.y_axis'),
                'visitsXAxis'          => $this->languagesDataHelper->translate('user.statistics.chart.visits.x_axis'),
                'participationsYAxis'  => $this->languagesDataHelper->translate('user.statistics.chart.participations.y_axis'),
                'participationsXAxis'  => $this->languagesDataHelper->translate('user.statistics.chart.participations.x_axis'),
            ],
        ]));
    }

    private function resolveSeason(): array
    {
        $schema = ['season' => FilterInputRule::DateInterval->value];
        $input  = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        [$start, $end] = explode('|', $input['season'] ?? '|');
        return $this->personalStatisticsDataHelper->getSeasonRange($start, $end);
    }

    private function getVisitCounts(array $season): array
    {
        $visits  = $this->logDataHelper->getVisits($season);
        $members = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        return $this->normalizeMemberCounts($members, $visits);
    }

    private function getParticipationCounts(array $season): array
    {
        $participations = $this->participantDataHelper->getParticipations($season);
        $members        = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        return $this->normalizeMemberCounts($members, $participations);
    }

    private function normalizeMemberCounts(array $members, array $rawCounts): array
    {
        $result = [];
        foreach ($members as $member) {
            $result[$member->Email] = (int) ($rawCounts[$member->Email] ?? 0);
        }
        return $result;
    }

    private function buildChartData(array $memberCounts, object $person): array
    {
        $dist             = $this->distributionCalculator->compute($memberCounts);
        $currentUserSlice = $this->distributionCalculator->findUserSlice(
            $dist['tranches'],
            $dist['memberCounts'],
            $person->Email
        );

        return array_map(
            fn($i, $tranche) => [
                'tranche'       => $tranche['label'],
                'count'         => $dist['distribution'][$i],
                'isCurrentUser' => ($i === $currentUserSlice),
            ],
            array_keys($dist['tranches']),
            $dist['tranches']
        );
    }
}