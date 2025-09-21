<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Registration implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $registrationController = fn() => $this->controllerFactory->makeRegistrationController();

        $this->routes[] = new Route('GET /registration', $registrationController, 'index');
        $this->routes[] = new Route('GET /registration/groups/@id:[0-9]+', $registrationController, 'getPersonGroups');

        return $this->routes;
    }
}
