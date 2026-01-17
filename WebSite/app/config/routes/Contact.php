<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Contact implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $contactController = fn() => $this->controllerFactory->makeContactController();

        $this->routes[] = new Route('GET  /contact/event/@id:[0-9]+', $contactController, 'contact');
        $this->routes[] = new Route('GET  /contact', $contactController, 'contact');
        $this->routes[] = new Route('POST /contact', $contactController, 'contact');

        return $this->routes;
    }
}
