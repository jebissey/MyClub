<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Pwa\PwaController;

final class Pwa implements RouteInterface
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
        $pwaController = fn() => $this->controllerFactory->make(PwaController::class);

        $this->routes[] = new Route('GET  /manifest.json', $pwaController, 'manifest');
        $this->routes[] = new Route('POST /share-target', $pwaController, 'handleShare');

        return $this->routes;
    }
}
