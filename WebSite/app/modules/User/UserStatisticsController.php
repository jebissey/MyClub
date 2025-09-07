<?php

namespace app\modules\User;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\LogDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\modules\Common\AbstractController;

class UserStatisticsController extends AbstractController
{
    private PersonStatisticsDataHelper $personalStatisticsDataHelper;
    private LogDataHelper $logDataHelper;

    public function __construct(Application $application, PersonStatisticsDataHelper $personalStatisticsDataHelper, LogDataHelper $logDataHelper)
    {
        parent::__construct($application);
        $this->personalStatisticsDataHelper = $personalStatisticsDataHelper;
        $this->logDataHelper = $logDataHelper;
    }

    public function showStatistics(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            $schema = [
                'seasonStart' => FilterInputRule::DateTime->value,
                'seasonEnd' => FilterInputRule::DateTime->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            $season = $this->personalStatisticsDataHelper->getSeasonRange($input['seasonStart'] ?? null, $input['seasonEnd'] ?? null);
            
            $this->render('User/views/user_statistics.latte', Params::getAll([
                'stats' => $this->personalStatisticsDataHelper->getStats($person, $season['start'], $season['end'], $this->connectedUser->isWebmaster()),
                'seasons' => $this->personalStatisticsDataHelper->getAvailableSeasons(),
                'currentSeason' => $season,
                'navItems' => $this->getNavItems($person),
                'chartData' => $this->getVisitStatsForChart($season, $person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    #region Private functions
    private function getVisitStatsForChart(array $season, object $person): array
    {
        $stats = $this->getVisitStats($season);
        $currentUserTranche = $this->getCurrentUserTranche($stats, $person);
        $chartData = [];
        
        for ($i = 0; $i < count($stats['tranches']); $i++) {
            $chartData[] = [
                'tranche' => $stats['tranches'][$i]['label'],
                'count' => $stats['distribution'][$i],
                'isCurrentUser' => ($i === $currentUserTranche)
            ];
        }
        return $chartData;
    }

    const SLICES = 100;
    
    private function getVisitStats($season)
    {
        $memberVisits = $this->getMemberVisits($season);
        $visitCounts = array_values($memberVisits);
        
        if (empty($visitCounts)) {
            return [
                'tranches' => [],
                'distribution' => [],
                'currentUserTranche' => null
            ];
        }
        
        $minVisits = min($visitCounts);
        $maxVisits = max($visitCounts);
        $trancheSize = max(1, ceil(($maxVisits - $minVisits) / self::SLICES));
        
        $tranches = [];
        for ($i = 0; $i < self::SLICES; $i++) {
            $start = $minVisits + ($i * $trancheSize);
            $end = $start + $trancheSize - 1;
            if ($i == self::SLICES - 1) {
                $end = $maxVisits;
            }
            $tranches[] = [
                'start' => $start,
                'end' => $end,
                'label' => "$start-$end"
            ];
        }
        
        $distribution = array_fill(0, count($tranches), 0);
        foreach ($memberVisits as $visits) {
            $index = ($trancheSize > 0)
                ? floor(($visits - $minVisits) / $trancheSize)
                : 0;
            if ($index >= self::SLICES) $index = self::SLICES - 1;
            $distribution[$index]++;
        }
        
        // Fusion des tranches vides
        $mergedTranches = [];
        $mergedDistribution = [];
        $currentTranche = null;
        $currentCount = 0;
        
        for ($i = 0; $i < count($tranches); $i++) {
            if ($distribution[$i] === 0) {
                if ($currentTranche === null) {
                    $currentTranche = $tranches[$i];
                    $currentCount = 0;
                } else {
                    $currentTranche['end'] = $tranches[$i]['end'];
                    $currentTranche['label'] = "{$currentTranche['start']}-{$currentTranche['end']}";
                }
            } else {
                if ($currentTranche !== null) {
                    $mergedTranches[] = $currentTranche;
                    $mergedDistribution[] = $currentCount;
                    $currentTranche = null;
                    $currentCount = 0;
                }
                $mergedTranches[] = $tranches[$i];
                $mergedDistribution[] = $distribution[$i];
            }
        }
        
        if ($currentTranche !== null) {
            $mergedTranches[] = $currentTranche;
            $mergedDistribution[] = $currentCount;
        }

        return [
            'tranches' => $mergedTranches,
            'distribution' => $mergedDistribution,
            'memberVisits' => $memberVisits
        ];
    }

    private function getMemberVisits($season)
    {
        $visits = $this->logDataHelper->getVisits($season);
        $memberVisits = [];
        $members = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        
        foreach ($members as $member) {
            $email = $member->Email;
            $memberVisits[$email] = isset($visits[$email]) ? (int)$visits[$email] : 0;
        }
        return $memberVisits;
    }

    private function getCurrentUserTranche($stats, $person)
    {
        if (empty($person) || empty($stats['memberVisits'])) {
            throw new \RuntimeException('$person or $stats can\'t be null in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        
        $email = $person->Email;
        if (!array_key_exists($email, $stats['memberVisits'])) {
            throw new \RuntimeException('User $email not found in stats in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        
        $userVisits = $stats['memberVisits'][$email];
        for ($i = 0; $i < count($stats['tranches']); $i++) {
            $tranche = $stats['tranches'][$i];
            if ($userVisits >= $tranche['start'] && $userVisits <= $tranche['end']) {
                return $i;
            }
        }
        
        Application::unreachable('User slice not found', __FILE__, __LINE__);
    }
}
