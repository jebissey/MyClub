<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class User implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userController = fn() => $this->controllerFactory->makeUserController();

        $this->routes[] = new Route('GET  /user/forgotPassword/@encodedEmail', $userController, 'forgotPassword');
        $this->routes[] = new Route('GET  /user/setPassword/@token:[a-f0-9]+', $userController, 'setPassword');
        $this->routes[] = new Route('POST /user/setPassword/@token:[a-f0-9]+', $userController, 'setPassword');
        $this->routes[] = new Route('GET  /user/sign/in', $userController, 'signIn');
        $this->routes[] = new Route('POST /user/sign/in', $userController, 'signIn');
        $this->routes[] = new Route('GET  /user/sign/out', $userController, 'signOut');
        
        return $this->routes;
    }
}
