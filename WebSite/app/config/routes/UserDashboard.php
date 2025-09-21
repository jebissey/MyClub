<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserDashboard implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userDashboardController = fn() => $this->controllerFactory->makeUserDashboardController();

        $this->routes[] = new Route('GET /user', $userDashboardController, 'user');
        $this->routes[] = new Route('GET /user/help', $userDashboardController, 'help');

        return $this->routes;
    }
}
