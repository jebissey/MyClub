<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class ArticleApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $articleApi = fn() => $this->apiFactory->makeArticleApi();

        $this->routes[] = new Route('GET  /api/author/@articleId:[0-9]+', $articleApi, 'getAuthor');
        $this->routes[] = new Route('POST /api/design/vote', $articleApi, 'designVote');
        $this->routes[] = new Route('POST /api/survey/reply', $articleApi, 'saveSurveyReply');
        $this->routes[] = new Route('GET  /api/survey/reply/@id:[0-9]+', $articleApi, 'showSurveyReplyForm');

        return $this->routes;
    }
}
