<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class VisitorInsightsApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $api = fn() => $this->apiFactory->makeVisitorInsightsApi();

        $this->routes[] = new Route('GET /api/visitor-insights/creation-time-distribution', $api, 'getCreationTimeDistribution');

        return $this->routes;
    }
}
