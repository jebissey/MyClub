<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $eventApi = fn() => $this->apiFactory->makeEventApi();

        $this->routes[] = new Route('POST /api/event/delete/@id:[0-9]+', $eventApi, 'deleteEvent');
        $this->routes[] = new Route('POST /api/event/duplicate/@id:[0-9]+', $eventApi, 'duplicateEvent');
        $this->routes[] = new Route('POST /api/event/save', $eventApi, 'saveEvent');
        $this->routes[] = new Route('POST /api/event/sendEmails', $eventApi, 'sendEmails');
        $this->routes[] = new Route('GET  /api/event/@id:[0-9]+', $eventApi, 'getEvent');

        return $this->routes;
    }
}
