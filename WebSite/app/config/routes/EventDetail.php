<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Event\EventDetailController;

final class EventDetail implements RouteInterface
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
        $eventDetailController = fn() => $this->controllerFactory->make(EventDetailController::class);

        $this->routes[] = new Route('GET  /event/@id:[0-9]+', $eventDetailController, 'show');
        $this->routes[] = new Route('GET  /event/chat/@id:[0-9]+', $eventDetailController, 'showEventChat');

        return $this->routes;
    }
}
