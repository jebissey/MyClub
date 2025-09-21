<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Maintenance implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $maintenanceController = fn() => $this->controllerFactory->makeMaintenanceController();

        $this->routes[] = new Route('GET /maintenance', $maintenanceController, 'maintenance');
        $this->routes[] = new Route('GET /maintenance/set', $maintenanceController, 'setSiteUnderMaintenance');
        $this->routes[] = new Route('GET /maintenance/unset', $maintenanceController, 'setSiteOnline');

        return $this->routes;
    }
}
