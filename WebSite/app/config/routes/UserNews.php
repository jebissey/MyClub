<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserNews implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userNewsController = fn() => $this->controllerFactory->makeUserNewsController();

        $this->routes[] = new Route('GET /user/news', $userNewsController, 'showNews');

        return $this->routes;
    }
}
