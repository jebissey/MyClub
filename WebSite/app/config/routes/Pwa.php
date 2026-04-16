<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Pwa implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $pwaController = fn() => $this->controllerFactory->makePwaController();

        $this->routes[] = new Route('GET  /manifest.json', $pwaController, 'manifest');
        $this->routes[] = new Route('POST /share-target', $pwaController, 'handleShare');

        return $this->routes;
    }
}