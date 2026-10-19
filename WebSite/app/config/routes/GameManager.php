<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class GameManager implements RouteInterface
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
        $gameManagerController = fn() => $this->controllerFactory->makeGameManagerController();

        $this->routes[] = new Route('GET /games', $gameManagerController, 'user');
        $this->routes[] = new Route('GET /games/help', $gameManagerController, 'help');

        return $this->routes;
    }
}
