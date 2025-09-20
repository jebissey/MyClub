<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventGuest implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $eventGuestController = fn() => $this->controllerFactory->makeEventGuestController();

        $this->routes[] = new Route('GET  /events/guest', $eventGuestController, 'guest');
        $this->routes[] = new Route('POST /events/guest', $eventGuestController, 'guestInvite');

        return $this->routes;
    }
}
