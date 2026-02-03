<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Order implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $orderController = fn() => $this->controllerFactory->makeOrderController();

        $this->routes[] = new Route('GET  /order/add/@id:[0-9]+', $orderController, 'add');
        $this->routes[] = new Route('POST /order/create', $orderController, 'createOrUpdate');
        $this->routes[] = new Route('GET  /order/results/@id:[0-9]+', $orderController, 'viewResults');

        return $this->routes;
    }
}