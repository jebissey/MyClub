<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Karaoke implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $karaokeController = fn() => $this->controllerFactory->makeKaraokeController();

        $this->routes[] = new Route('GET /game/karaoke/@song:[A-Za-z0-9_]+', $karaokeController, 'play');
        $this->routes[] = new Route('GET /game/karaoke/files/@name:[A-Za-z0-9_]+', $karaokeController, 'files');

        return $this->routes;
    }
}
