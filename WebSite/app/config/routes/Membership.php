<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Membership implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    public function get(): array
    {
        $membershipController = fn() => $this->controllerFactory->makeMembershipController();

        $this->routes[] = new Route('GET /user/membership', $membershipController, 'index');
        $this->routes[] = new Route('GET /user/membership/return', $membershipController, 'index'); // handled by controller

        return $this->routes;
    }
}
