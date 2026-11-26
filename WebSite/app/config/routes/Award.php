<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Awards\AwardsController;

final class Award implements RouteInterface
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
        $awardsController = fn() => $this->controllerFactory->make(AwardsController::class);

        $this->routes[] = new Route('GET  /awards', $awardsController, 'seeAwards');
        $this->routes[] = new Route('POST /award', $awardsController, 'setAward');

        return $this->routes;
    }
}
