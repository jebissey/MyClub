<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserConnections implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userConnectionsController = fn() => $this->controllerFactory->makeUserConnectionsController();

        $this->routes[] = new Route('GET /user/connections', $userConnectionsController, 'showConnections_');
        $this->routes[] = new Route('GET /user/connections/@id:[0-9]+', $userConnectionsController, 'showConnections');

        return $this->routes;
    }
}
