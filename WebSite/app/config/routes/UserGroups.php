<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserGroups implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userGroupsController = fn() => $this->controllerFactory->makeUserGroupsController();

        $this->routes[] = new Route('GET  /user/groups', $userGroupsController, 'groups');
        $this->routes[] = new Route('POST /user/groups', $userGroupsController, 'groupsSave');

        return $this->routes;
    }
}
