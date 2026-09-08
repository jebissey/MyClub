<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

class VisitorInsights implements RouteInterface
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
        $visitorInsightsController = fn() => $this->controllerFactory->makeVisitorInsightsController();

        $this->routes[] = new Route('GET /analytics', $visitorInsightsController, 'analytics');
        $this->routes[] = new Route('GET /analytics/help', $visitorInsightsController, 'helpPage', ['analytics']);

        $this->routes[] = new Route('GET /crossTab', $visitorInsightsController, 'crossTab');
        $this->routes[] = new Route('GET /crossTab/help', $visitorInsightsController, 'helpPage', ['crossTab']);

        $this->routes[] = new Route('GET /lastVisits', $visitorInsightsController, 'showLastVisits');
        $this->routes[] = new Route('GET /lastVisits/help', $visitorInsightsController, 'helpPage', ['lastVisits']);

        $this->routes[] = new Route('GET /logs', $visitorInsightsController, 'index');
        $this->routes[] = new Route('GET /logs/help', $visitorInsightsController, 'helpPage', ['logs']);

        $this->routes[] = new Route('GET /membersAlerts', $visitorInsightsController, 'membersAlerts');
        $this->routes[] = new Route('GET /membersAlerts/help', $visitorInsightsController, 'helpPage', ['membersAlerts']);

        $this->routes[] = new Route('GET /referents', $visitorInsightsController, 'referents');
        $this->routes[] = new Route('GET /referents/help', $visitorInsightsController, 'helpPage', ['referents']);

        $this->routes[] = new Route('GET /topPages', $visitorInsightsController, 'topPagesByPeriod');
        $this->routes[] = new Route('GET /topPages/help', $visitorInsightsController, 'helpPage', ['topPages']);

        $this->routes[] = new Route('GET /visitorInsights', $visitorInsightsController, 'visitorInsights');
        $this->routes[] = new Route('GET /visitorInsights/help', $visitorInsightsController, 'helpPage', ['visitorInsights']);

        $this->routes[] = new Route('GET /visitorsGraf', $visitorInsightsController, 'visitorsGraf');
        $this->routes[] = new Route('GET /visitorsGraf/help', $visitorInsightsController, 'helpPage', ['visitorsGraf']);

        return $this->routes;
    }
}
