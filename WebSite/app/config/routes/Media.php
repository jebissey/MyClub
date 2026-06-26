<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Media implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    public function get(): array
    {
        $mediaController = fn() => $this->controllerFactory->makeMediaController();

        $this->routes[] = new Route('GET /data/media/@year:[0-9]{4}/@month:[0-9]{2}/@filename', $mediaController, 'serveFile');
        $this->routes[] = new Route('GET /media/@year:[0-9]{4}/@month:[0-9]{2}/@filename', $mediaController, 'viewFileForRedactor');
        $this->routes[] = new Route('GET /media/list', $mediaController, 'listFiles');
        $this->routes[] = new Route('GET /media/gpxViewer', $mediaController, 'gpxViewer');
        $this->routes[] = new Route('GET /media/isShared', $mediaController, 'isShared');
        $this->routes[] = new Route('GET /media/sharedFile/@token:[a-f0-9]+', $mediaController, 'getSharedFile');
        $this->routes[] = new Route('GET /media/upload', $mediaController, 'showUploadForm');
        $this->routes[] = new Route('GET /media/uses/inArticles', $mediaController, 'showUsesInArticles');
        $this->routes[] = new Route('GET /media/uses/inMessages', $mediaController, 'showUsesInMessages');

        return $this->routes;
    }
}
