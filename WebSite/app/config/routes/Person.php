<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Person implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $personController = fn() => $this->controllerFactory->makePersonController();

        $this->routes[] = new Route('GET  /personManager', $personController, 'home');
        $this->routes[] = new Route('GET  /personManager/help', $personController, 'help');
        $this->routes[] = new Route('GET  /persons', $personController, 'index');
        $this->routes[] = new Route('GET  /person/create', $personController, 'create');
        $this->routes[] = new Route('GET  /person/edit/@id:[0-9]+', $personController, 'edit');
        $this->routes[] = new Route('POST /person/edit/@id:[0-9]+', $personController, 'editSave');
        $this->routes[] = new Route('GET  /person/delete/@id:[0-9]+', $personController, 'delete');

        return $this->routes;
    }
}
