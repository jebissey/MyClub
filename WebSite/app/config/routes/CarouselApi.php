<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class CarouselApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $carouselApi = fn() => $this->apiFactory->makeCarouselApi();

        $this->routes[] = new Route('GET  /api/carousel/@articleId:[0-9]+', $carouselApi, 'getItems');
        $this->routes[] = new Route('POST /api/carousel/save', $carouselApi, 'saveItem');
        $this->routes[] = new Route('POST /api/carousel/delete/@id:[0-9]+', $carouselApi, 'deleteItem');

        return $this->routes;
    }
}
