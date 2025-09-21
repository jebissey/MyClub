<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Import implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $importController = fn() => $this->controllerFactory->makeImportController();

        $this->routes[] = new Route('GET  /import', $importController, 'showImportForm');
        $this->routes[] = new Route('POST /import', $importController, 'processImport');

        return $this->routes;
    }
}
