<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class VisitorInsights implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $visitorInsightsController = fn() => $this->controllerFactory->makeVisitorInsightsController();

        $this->routes[] = new Route('GET  /visitorInsights', $visitorInsightsController, 'visitorInsights');
        $this->routes[] = new Route('GET  /visitorInsights/help', $visitorInsightsController, 'helpVisitorInsights');

        return $this->routes;
    }
}
