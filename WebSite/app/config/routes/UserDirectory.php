<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

class UserDirectory implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $userDirectoryController = fn() => $this->controllerFactory->makeUserDirectoryController();

        $this->routes[] = new Route('GET /user/directory', $userDirectoryController, 'showDirectory');
        $this->routes[] = new Route('GET /user/directory/map', $userDirectoryController, 'showMap');
        $this->routes[] = new Route('GET /user/directory/public/map', $userDirectoryController, 'showPublicMap');

        return $this->routes;
    }
}
