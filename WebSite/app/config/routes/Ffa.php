<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\User\FFAController;

final class Ffa implements RouteInterface
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
        $ffaController = fn() => $this->controllerFactory->make(FFAController::class);

        $this->routes[] = new Route('GET /ffa/search', $ffaController, 'searchMember');

        return $this->routes;
    }
}
