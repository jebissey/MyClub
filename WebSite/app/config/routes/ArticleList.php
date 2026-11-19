<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Article\ArticleListController;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class ArticleList implements RouteInterface
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
        $articleListController = fn() => $this->controllerFactory->make(ArticleListController::class);

        $this->routes[] = new Route('GET  /articles', $articleListController, 'index');
        $this->routes[] = new Route('GET  /articles/public', $articleListController, 'publicIndex');

        return $this->routes;
    }
}
