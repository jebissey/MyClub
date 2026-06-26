<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class ExerciseApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory)
    {
    }

    public function get(): array
    {
        $api = fn() => $this->apiFactory->makeExerciseApi();

        $this->routes[] = new Route('GET  /api/exercise/@id:[0-9]+', $api, 'get');
        $this->routes[] = new Route('POST /api/exercise/save/@id:[0-9]+', $api, 'save');
        $this->routes[] = new Route('POST /api/exercise/delete/@id:[0-9]+', $api, 'delete');

        return $this->routes;
    }
}
