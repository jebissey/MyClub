<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserStatistics implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userStatisticsController = fn() => $this->controllerFactory->makeUserStatisticsController();

        $this->routes[] = new Route('GET /user/statistics', $userStatisticsController, 'showStatistics');

        return $this->routes;
    }
}
