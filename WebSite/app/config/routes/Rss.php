<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Rss implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $rssController = fn() => $this->controllerFactory->makeRssController();

        $this->routes[] = new Route('GET /articles-rss.xml', $rssController, 'articlesRssGenerator');
        $this->routes[] = new Route('GET /events-rss.xml', $rssController, 'eventsRssGenerator');

        return $this->routes;
    }
}
