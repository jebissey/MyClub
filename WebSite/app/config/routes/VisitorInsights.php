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

        $this->routes[] = new Route('GET /analytics', $visitorInsightsController, 'analytics');
        $this->routes[] = new Route('GET /crossTab', $visitorInsightsController, 'crossTab');
        $this->routes[] = new Route('GET /lastVisits', $visitorInsightsController, 'showLastVisits');
        $this->routes[] = new Route('GET /membersAlerts', $visitorInsightsController, 'membersAlerts');
        $this->routes[] = new Route('GET /logs', $visitorInsightsController, 'index');
        $this->routes[] = new Route('GET /referents', $visitorInsightsController, 'referents');
        $this->routes[] = new Route('GET /topArticles', $visitorInsightsController, 'topArticlesByPeriod');
        $this->routes[] = new Route('GET /topPages', $visitorInsightsController, 'topPagesByPeriod');
        $this->routes[] = new Route('GET /visitors/graf', $visitorInsightsController, 'visitorsGraf');
        $this->routes[] = new Route('GET /visitorInsights', $visitorInsightsController, 'visitorInsights');
        $this->routes[] = new Route('GET /visitorInsights/help', $visitorInsightsController, 'helpVisitorInsights');

        return $this->routes;
    }
}
