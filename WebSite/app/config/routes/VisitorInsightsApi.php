<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class VisitorInsightsApi implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $api = fn() => $this->apiFactory->makeVisitorInsightsApi();

        $this->routes[] = new Route('GET /api/visitor-insights/creation-time-distribution', $api, 'getCreationTimeDistribution');
        $this->routes[] = new Route('GET /api/visitor-insights/creation-time-trend', $api, 'getCreationTimeTrend');

        return $this->routes;
    }
}
