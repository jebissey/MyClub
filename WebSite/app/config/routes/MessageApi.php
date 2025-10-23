<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class MessageApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $eventMessageApi = fn() => $this->apiFactory->makeMessageApi();

        $this->routes[] = new Route('POST /api/message/add', $eventMessageApi, 'addMessage');
        $this->routes[] = new Route('POST /api/message/update', $eventMessageApi, 'updateMessage');
        $this->routes[] = new Route('POST /api/message/delete', $eventMessageApi, 'deleteMessage');

        return $this->routes;
    }
}
