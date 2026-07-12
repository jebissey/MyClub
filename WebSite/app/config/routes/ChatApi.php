<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class ChatApi implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $chatApi = fn() => $this->apiFactory->makeChatApi();

        $this->routes[] = new Route('GET /api/chat/active-users', $chatApi, 'getActiveUsers');

        return $this->routes;
    }
}
