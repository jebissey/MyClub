<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Article implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $articleController = fn() => $this->controllerFactory->makeArticleController();

        $this->routes[] = new Route('GET  /article/create', $articleController, 'create');
        $this->routes[] = new Route('POST /article/delete/@id:[0-9]+', $articleController, 'delete');
        $this->routes[] = new Route('GET  /article/@id:[0-9]+', $articleController, 'show');
        $this->routes[] = new Route('POST /article/@id:[0-9]+', $articleController, 'update');
        $this->routes[] = new Route('GET  /articles', $articleController, 'index');
        $this->routes[] = new Route('GET  /articles/crosstab', $articleController, 'showArticleCrosstab');
        $this->routes[] = new Route('GET  /emails/article/@id:[0-9]+', $articleController, 'fetchEmailsForArticle');
        $this->routes[] = new Route('GET  /publish/article/@id:[0-9]+', $articleController, 'publish');
        $this->routes[] = new Route('POST /publish/article/@id:[0-9]+', $articleController, 'publish');
        $this->routes[] = new Route('GET  /redactor', $articleController, 'home');
        $this->routes[] = new Route('GET  /redactor/help', $articleController, 'help');

        return $this->routes;
    }
}
