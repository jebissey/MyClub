<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserPresentation implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userPresentationController = fn() => $this->controllerFactory->makeUserPresentationController();

        $this->routes[] = new Route('GET  /user/presentation/edit', $userPresentationController, 'editPresentation');
        $this->routes[] = new Route('POST /user/presentation/edit', $userPresentationController, 'savePresentation');
        $this->routes[] = new Route('GET  /user/presentation/@id:[0-9]+', $userPresentationController, 'showPresentation');

        return $this->routes;
    }
}
