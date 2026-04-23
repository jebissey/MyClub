<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Loan implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $loanController = fn() => $this->controllerFactory->makeLoanController();

        $this->routes[] = new Route('GET /loan/calendar',      $loanController, 'calendar');
        $this->routes[] = new Route('GET /loan',               $loanController, 'designer');
        $this->routes[] = new Route('GET /loan/designer/help', $loanController, 'designerHelp');
        $this->routes[] = new Route('GET /loan/manager',       $loanController, 'manager');
        $this->routes[] = new Route('GET /loan/reservations',  $loanController, 'reservations');

        return $this->routes;
    }
}
