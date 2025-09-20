<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class EventEmail implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $eventEmailController = fn() => $this->controllerFactory->makeEventEmailController();

        $this->routes[] = new Route('GET  /emails', $eventEmailController, 'fetchEmails');
        $this->routes[] = new Route('POST /emails', $eventEmailController, 'copyEmails');

        return $this->routes;
    }
}
