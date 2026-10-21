<?php

declare(strict_types=1);

namespace app\apis;

use DateTimeImmutable;
use Exception;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\AvailabilityDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;

final class EventAvailabilitiesApi extends AbstractApi
{
    /** @var array<string, string> */
    private const RANGE_MODIFIERS = [
        '1w' => '1 week',
        '5w' => '5 weeks',
        '3m' => '3 months',
        '6m' => '6 months',
        '9m' => '9 months',
        '1y' => '1 year',
    ];

    public function __construct(
        Application $application,
        protected ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly AvailabilityDataHelper $availabilityDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function getSlotEvents(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'day'       => FilterInputRule::Int->value,
            'slot'      => ['morning', 'afternoon', 'evening'],
            'range'     => array_keys(self::RANGE_MODIFIERS),
            'startDate' => FilterInputRule::DateTime->value,
        ];
        $input = WebApp::filterInput($schema, $this->application->getFlight()->request()->query->getData());

        $day  = To::int($input['day']);
        $slot = $input['slot'];

        if ($input['day'] === null || $day < 0 || $day > 6 || !is_string($slot)) {
            $this->renderJsonBadRequest('', __FILE__, __LINE__);
            return;
        }

        $rangeKey = is_string($input['range']) ? $input['range'] : '6m';
        $modifier = self::RANGE_MODIFIERS[$rangeKey];

        $startParam = $input['startDate'];
        try {
            $start = is_string($startParam)
                ? new DateTimeImmutable($startParam)
                : (new DateTimeImmutable())->modify('-' . $modifier);
        } catch (Exception) {
            $start = (new DateTimeImmutable())->modify('-' . $modifier);
        }
        $end = $start->modify('+' . $modifier);

        $events = $this->availabilityDataHelper->getEventsForSlot($start, $end, $day, $slot);

        $this->renderJsonOk(['events' => $events]);
    }
}
