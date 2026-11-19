<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\User\UserGroupsController;

final class UserGroups implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $userGroupsController = fn() => $this->controllerFactory->make(UserGroupsController::class);

        $this->routes[] = new Route('GET  /user/groups', $userGroupsController, 'groups');
        $this->routes[] = new Route('POST /user/groups', $userGroupsController, 'groupsSave');

        return $this->routes;
    }
}
