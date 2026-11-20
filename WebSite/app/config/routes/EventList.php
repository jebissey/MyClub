<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Event\EventListController;

final class EventList implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $eventListController = fn() => $this->controllerFactory->make(EventListController::class);

        $this->routes[] = new Route('GET  /nextEvents', $eventListController, 'nextEvents');
        $this->routes[] = new Route('GET  /nextEvents/help', $eventListController, 'nextEventsHelp');

        $this->routes[] = new Route('GET  /weekEvents', $eventListController, 'weekEvents');
        $this->routes[] = new Route('GET  /weekEvents/help', $eventListController, 'weekEventsHelp');

        $this->routes[] = new Route('GET  /events/guest/help', $eventListController, 'events_guest_help');

        return $this->routes;
    }
}
