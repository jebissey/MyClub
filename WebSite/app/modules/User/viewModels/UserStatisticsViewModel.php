<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\valueObjects\MenuItemRow;
use app\modules\Common\valueObjects\Person;
use app\modules\Common\viewModels\LayoutViewModel;

/**
 * @phpstan-type PercentageStat array{
 *     user: int,
 *     total: int,
 *     percentage: int|float
 * }
 *
 * @phpstan-type EventStat array{
 *     typeName: string,
 *     user: int,
 *     total: int,
 *     percentage: int|float
 * }
 *
 * @phpstan-type EventParticipationStat array{
 *     typeName: string,
 *     events: int,
 *     user: int,
 *     total: int,
 *     percentage: int|float
 * }
 *
 * @phpstan-type ParticipantMessageStat array{
 *     user: int,
 *     totalUsers: int,
 *     percentage: int|float,
 *     webapp: int,
 *     totalWebapp: int,
 *     percentageWebapp: int|float
 * }
 *
 * @phpstan-type UserStatistics array{
 *     person: Person,
 *     seasonStart: string,
 *     seasonEnd: string,
 *     articles: PercentageStat,
 *     surveys: PercentageStat,
 *     surveyReplies: PercentageStat,
 *     designs: PercentageStat,
 *     designVotes: PercentageStat,
 *     events: array<int|string, EventStat>,
 *     eventParticipations: array<int|string, EventParticipationStat>,
 *     participantSupplies: PercentageStat,
 *     participantMessages: ParticipantMessageStat
 * }
 */
final readonly class UserStatisticsViewModel extends LayoutViewModel
{
    /**
     * @param UserStatistics $stats
     * @param list<array{label: string, start: string, end: string}> $seasons
     * @param array{start: string, end: string} $currentSeason
     * @param list<MenuItemRow> $navItems
     * @param list<array{tranche: string, count: int, isHighlighted: bool}> $chartData
     * @param list<array{tranche: string, count: int, isHighlighted: bool}> $participationChartData
     * @param list<array{tranche: string, count: int, isHighlighted: bool}> $messageChartData
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $stats,
        public array $seasons,
        public array $currentSeason,
        public array $navItems,
        public array $chartData,
        public array $participationChartData,
        public array $messageChartData,
        public array $i18n,
        array $layoutParams = []
    ) {
        $baseArgs = self::baseArgsFrom($layoutParams);
        $baseArgs['page'] = 'statistics';

        parent::__construct(
            ...$baseArgs,
            btnHistoryBack: true,
            btnParent: '/user',
        );
    }
}
