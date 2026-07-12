<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserPreferences implements RouteInterface
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
        $userPreferencesController = fn() => $this->controllerFactory->makeUserPreferencesController();

        $this->routes[] = new Route('GET  /user/preferences', $userPreferencesController, 'preferences');
        $this->routes[] = new Route('POST /user/preferences', $userPreferencesController, 'preferencesSave');

        return $this->routes;
    }
}
