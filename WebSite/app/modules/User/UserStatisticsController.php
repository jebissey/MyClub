<?php

declare(strict_types=1);

namespace app\modules\User;

use stdClass;
use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\DistributionCalculator;
use app\helpers\MyClubDateTime;
use app\helpers\WebApp;
use app\models\LogDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\modules\Common\AbstractController;
use app\valueObjects\Person;

class UserStatisticsController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonStatisticsDataHelper $personalStatisticsDataHelper,
        private LogDataHelper $logDataHelper,
        private ParticipantDataHelper $participantDataHelper,
        private DistributionCalculator $distributionCalculator,
        private MessageDataHelper $messageDataHelper,
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
            'stats' => $this->personalStatisticsDataHelper->getStats($person, $season['start'], $season['end']),
            'seasons'                => $this->personalStatisticsDataHelper->getAvailableSeasons(),
            'currentSeason'          => $season,
            'navItems'               => $this->getNavItems($person),
            'chartData'              => $this->buildChartData($this->getVisitCounts($season), $person),
            'participationChartData' => $this->buildChartData($this->getParticipationCounts($season), $person),
            'messageChartData'       => $this->buildChartData($this->getMessageCounts($season), $person),
            'page'                   => $this->application->getConnectedUser()->getPage(1),
            'btn_HistoryBack'        => true,
            'btn_Parent'             => "/user",
            'i18n' => [
                'visitsYAxis' => ($this->t)('user.statistics.chart.visits.y_axis'),
                'visitsXAxis' => ($this->t)('user.statistics.chart.visits.x_axis'),
                'participationsYAxis' => ($this->t)('user.statistics.chart.participations.y_axis'),
                'participationsXAxis' => ($this->t)('user.statistics.chart.participations.x_axis'),
                'messagesYAxis' => ($this->t)('user.statistics.chart.messages.y_axis'),
                'messagesXAxis' => ($this->t)('user.statistics.chart.messages.x_axis'),
            ],
        ]));
    }

    /**
     * @return array{start: string, end: string}
     */
    private function resolveSeason(): array
    {
        $schema = ['season' => FilterInputRule::DateInterval->value];

        $input = WebApp::filterInput(
            $schema,
            $this->flight->request()->query->getData()
        );

        $season = $input['season'] ?? '';

        if (!is_string($season)) {
            $season = '';
        }

        [$start, $end] = explode('|', $season . '|', 2);

        return MyClubDateTime::getSeasonRange($start, $end);
    }

    /**
     * @param  array{start: string, end: string} $season
     * @return array<string, int>
     */
    private function getVisitCounts(array $season): array
    {
        $visits  = $this->logDataHelper->getVisits($season);
        $members = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        return $this->normalizeMemberCounts($members, $visits);
    }

    /**
     * @param  array{start: string, end: string} $season
     * @return array<string, int>
     */
    private function getParticipationCounts(array $season): array
    {
        $participations = $this->participantDataHelper->getParticipations($season);
        $members        = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        return $this->normalizeMemberCounts($members, $participations);
    }

    /**
     * @param  array{start: string, end: string} $season
     * @return array<string, int>
     */
    private function getMessageCounts(array $season): array
    {
        $messages = $this->messageDataHelper->getMessages($season);
        $members  = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        return $this->normalizeMemberCounts($members, $messages);
    }

    /**
     * @param  array<int|string, stdClass>  $members
     * @param  array<string, int|string> $rawCounts
     * @return array<string, int>
     */
    private function normalizeMemberCounts(array $members, array $rawCounts): array
    {
        $result = [];
        foreach ($members as $member) {
            $result[$member->Email] = (int) ($rawCounts[$member->Email] ?? 0);
        }
        return $result;
    }

    /**
     * @param  array<string, int> $memberCounts
     * @return array<int, array{tranche: string, count: int, isHighlighted: bool}>
     */
    private function buildChartData(array $memberCounts, Person $person): array
    {
        $dist             = $this->distributionCalculator->compute($memberCounts);
        $currentUserSlice = $this->distributionCalculator->findUserSlice(
            $dist['tranches'],
            $dist['memberCounts'],
            $person->Email
        );

        return array_map(
            fn(int $i, array $tranche) => [
                'tranche'       => $tranche['label'],
                'count'         => $dist['distribution'][$i],
                'isHighlighted' => ($i === $currentUserSlice),
            ],
            array_keys($dist['tranches']),
            $dist['tranches']
        );
    }
}
