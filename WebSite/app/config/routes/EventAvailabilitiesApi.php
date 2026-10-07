<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class EventAvailabilitiesApi implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly ApiFactory $apiFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $eventAvailabilitiesApi = fn() => $this->apiFactory->makeEventAvailabilitiesApi();

        $this->routes[] = new Route('GET /api/event-availabilities/slot-events', $eventAvailabilitiesApi, 'getSlotEvents');

        return $this->routes;
    }
}
