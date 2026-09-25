<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class Astronomy implements RouteInterface
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
        $astronomyController = fn() => $this->controllerFactory->makeAstronomyController();

        $this->routes[] = new Route('GET  /astronomy', $astronomyController, 'show');
        $this->routes[] = new Route('POST /astronomy/save', $astronomyController, 'saveLocation');

        return $this->routes;
    }
}
