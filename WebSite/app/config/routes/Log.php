<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Log implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $logController = fn() => $this->controllerFactory->makeLogController();

        $this->routes[] = new Route('GET /analytics', $logController, 'analytics');
        $this->routes[] = new Route('GET /lastVisits', $logController, 'showLastVisits');
        $this->routes[] = new Route('GET /logs', $logController, 'index');
        $this->routes[] = new Route('GET /logs/crossTab', $logController, 'crossTab');
        $this->routes[] = new Route('GET /referents', $logController, 'referents');
        $this->routes[] = new Route('GET /topArticles', $logController, 'topArticlesByPeriod');
        $this->routes[] = new Route('GET /topPages', $logController, 'topPagesByPeriod');
        $this->routes[] = new Route('GET /visitors/graf', $logController, 'visitorsGraf');

        return $this->routes;
    }
}
