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
        $c = fn() => $this->controllerFactory->makeVisitorInsightsController();

        // Feature routes
        $this->routes[] = new Route('GET /analytics',       $c, 'analytics');
        $this->routes[] = new Route('GET /crossTab',        $c, 'crossTab');
        $this->routes[] = new Route('GET /lastVisits',      $c, 'showLastVisits');
        $this->routes[] = new Route('GET /logs',            $c, 'index');
        $this->routes[] = new Route('GET /membersAlerts',   $c, 'membersAlerts');
        $this->routes[] = new Route('GET /referents',       $c, 'referents');
        $this->routes[] = new Route('GET /topPages',        $c, 'topPagesByPeriod');
        $this->routes[] = new Route('GET /visitorInsights', $c, 'visitorInsights');
        $this->routes[] = new Route('GET /visitorsGraf',    $c, 'visitorsGraf');

        // Help routes — single controller method, section passed as argument
        foreach (
            [
                'analytics',
                'crossTab',
                'lastVisits',
                'logs',
                'membersAlerts',
                'referents',
                'topPages',
                'visitorInsights',
                'visitorsGraf',
            ] as $section
        ) {
            $this->routes[] = new Route(
                "GET /{$section}/help",
                $c,
                'helpPage',
                [$section]          // passed as argument to helpPage(string $section)
            );
        }

        return $this->routes;
    }
}
