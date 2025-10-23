<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserMessages implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userMessagesController = fn() => $this->controllerFactory->makeUserMessagesController();

        $this->routes[] = new Route('GET  /user/messages', $userMessagesController, 'showMessages');

        return $this->routes;
    }
}
