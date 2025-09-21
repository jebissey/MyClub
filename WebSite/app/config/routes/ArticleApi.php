<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class ArticleApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $articleApi = fn() => $this->controllerFactory->makeArticleApi();

        $this->routes[] = new Route('GET  /api/author/@articleId:[0-9]+', $articleApi, 'getAuthor');
        $this->routes[] = new Route('POST /api/design/vote', $articleApi, 'designVote');
        $this->routes[] = new Route('POST /api/media/delete/@year:[0-9]+/@month:[0-9]+/@filename', $articleApi, 'deleteFile');
        $this->routes[] = new Route('POST /api/media/upload', $articleApi, 'uploadFile');
        $this->routes[] = new Route('POST /api/survey/reply', $articleApi, 'saveSurveyReply');
        $this->routes[] = new Route('GET  /api/survey/reply/@id:[0-9]+', $articleApi, 'showSurveyReplyForm');

        return $this->routes;
    }
}
