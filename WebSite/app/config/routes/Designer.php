<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Designer\DesignerController;

final class Designer implements RouteInterface
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
        $designerController = fn() => $this->controllerFactory->make(DesignerController::class);

        $this->routes[] = new Route('GET  /designer', $designerController, 'homeDesigner');
        $this->routes[] = new Route('GET  /designer/help', $designerController, 'help');

        $this->routes[] = new Route('GET  /routes', $designerController, 'showRoutes');
        $this->routes[] = new Route('GET  /routes/help', $designerController, 'routesHelp');
        $this->routes[] = new Route('GET  /routes/help/@function', $designerController, 'showRouteHelp');

        return $this->routes;
    }
}
