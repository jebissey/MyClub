<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class DbBrowser implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $dbBrowserController = fn() => $this->controllerFactory->makeDbBrowserController();

        $this->routes[] = new Route('GET  /dbbrowser', $dbBrowserController, 'index');
        $this->routes[] = new Route('GET  /dbbrowser/@table:[A-Za-z0-9_]+', $dbBrowserController, 'showTable', 1);
        $this->routes[] = new Route('GET  /dbbrowser/@table:[A-Za-z0-9_]+/create', $dbBrowserController, 'showCreateForm');
        $this->routes[] = new Route('POST /dbbrowser/@table:[A-Za-z0-9_]+/create', $dbBrowserController, 'createRecord');
        $this->routes[] = new Route('GET  /dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+', $dbBrowserController, 'showEditForm');
        $this->routes[] = new Route('POST /dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+', $dbBrowserController, 'updateRecord');
        $this->routes[] = new Route('POST /dbbrowser/@table:[A-Za-z0-9_]+/delete/@id:[0-9]+', $dbBrowserController, 'deleteRecord');

        return $this->routes;
    }
}
