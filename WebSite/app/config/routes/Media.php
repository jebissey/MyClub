<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Media implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $mediaController = fn() => $this->controllerFactory->makeMediaController();

        $this->routes[] = new Route('GET /media/@year:[0-9]+/@month:[0-9]+/@filename', $mediaController, 'viewFile');
        $this->routes[] = new Route('GET /media/upload', $mediaController, 'showUploadForm');
        $this->routes[] = new Route('GET /media/list', $mediaController, 'listFiles');
        $this->routes[] = new Route('GET /media/gpxViewer', $mediaController, 'gpxViewer');

        return $this->routes;
    }
}
