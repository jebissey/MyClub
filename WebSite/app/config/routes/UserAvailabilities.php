<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserAvailabilities implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userAvailabilitiesController = fn() => $this->controllerFactory->makeUserAvailabilitiesController();

        $this->routes[] = new Route('GET  /user/availabilities', $userAvailabilitiesController, 'availabilities');
        $this->routes[] = new Route('POST /user/availabilities', $userAvailabilitiesController, 'availabilitiesSave');

        return $this->routes;
    }
}
