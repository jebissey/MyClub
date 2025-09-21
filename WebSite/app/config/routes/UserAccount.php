<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserAccount implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userAccountController = fn() => $this->controllerFactory->makeUserAccountController();

        $this->routes[] = new Route('GET  /user/account', $userAccountController, 'account');
        $this->routes[] = new Route('POST /user/account', $userAccountController, 'accountSave');

        return $this->routes;
    }
}
