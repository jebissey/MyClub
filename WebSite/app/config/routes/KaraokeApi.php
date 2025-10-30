<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class KaraokeApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $karaokeApi = fn() => $this->apiFactory->makeKaraokeApi();

        $this->routes[] = new Route('GET /api/karaoke', $karaokeApi, 'handleApiRequest');

        return $this->routes;
    }
}
