<?php

declare(strict_types=1);

namespace app\apis;

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
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $day  = To::int($_GET['day'] ?? null);
        $slot = $_GET['slot'] ?? null;

        if ($day < 0 || $day > 6 || !in_array($slot, ['morning', 'afternoon', 'evening'], true)) {
            $this->renderJsonBadRequest('', __FILE__, __LINE__);
            return;
        }

        $rangeParam = $_GET['range'] ?? null;
        $rangeKey   = is_string($rangeParam) && isset(self::RANGE_MODIFIERS[$rangeParam]) ? $rangeParam : '6m';

        $startParam = $_GET['startDate'] ?? null;
        try {
            $start = (is_string($startParam) && $startParam !== '')
                ? new \DateTimeImmutable($startParam)
                : (new \DateTimeImmutable())->modify('-' . self::RANGE_MODIFIERS[$rangeKey]);
        } catch (\Exception) {
            $start = (new \DateTimeImmutable())->modify('-' . self::RANGE_MODIFIERS[$rangeKey]);
        }
        $end = $start->modify('+' . self::RANGE_MODIFIERS[$rangeKey]);

        $events = $this->availabilityDataHelper->getEventsForSlot($start, $end, $day, $slot);

        $this->renderJsonOk(['events' => $events]);
    }
}
