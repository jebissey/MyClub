<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

class UserStatistics implements RouteInterface
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
        $userStatisticsController = fn() => $this->controllerFactory->makeUserStatisticsController();

        $this->routes[] = new Route('GET /user/statistics', $userStatisticsController, 'showStatistics');

        return $this->routes;
    }
}
