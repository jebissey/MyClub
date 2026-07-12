<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserDashboard implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $userDashboardController = fn() => $this->controllerFactory->makeUserDashboardController();

        $this->routes[] = new Route('GET /user', $userDashboardController, 'user');
        $this->routes[] = new Route('GET /user/help', $userDashboardController, 'help');

        return $this->routes;
    }
}
