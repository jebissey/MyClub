<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Arward implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $arwardsController = fn() => $this->controllerFactory->makeArwardsController();

        $this->routes[] = new Route('GET  /arwards', $arwardsController, 'seeArwards');
        $this->routes[] = new Route('POST /arward', $arwardsController, 'setArward');

        return $this->routes;
    }
}
