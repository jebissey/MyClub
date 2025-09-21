<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Ffa implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $ffaController = fn() => $this->controllerFactory->makeFfaController();

        $this->routes[] = new Route('GET /ffa/search', $ffaController, 'searchMember');

        return $this->routes;
    }
}
