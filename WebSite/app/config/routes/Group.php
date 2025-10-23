<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Group implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $groupController = fn() => $this->controllerFactory->makeGroupController();

        $this->routes[] = new Route('GET  /group/chat/@id:[0-9]+', $groupController, 'showGroupChat');
        $this->routes[] = new Route('GET  /group/create', $groupController, 'groupCreate');
        $this->routes[] = new Route('POST /group/create', $groupController, 'groupCreateSave');
        $this->routes[] = new Route('GET  /group/edit/@id:[0-9]+', $groupController, 'groupEdit');
        $this->routes[] = new Route('POST /group/edit/@id:[0-9]+', $groupController, 'groupEditSave');
        $this->routes[] = new Route('POST /group/delete/@id:[0-9]+', $groupController, 'groupDelete');
        $this->routes[] = new Route('GET  /groups', $groupController, 'groupIndex');

        return $this->routes;
    }
}
