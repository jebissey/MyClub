<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class KanbanApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $kanbanApi = fn() => $this->apiFactory->makeKanbanApi();

        // Récupérer les cartes
        $this->routes[] = new Route('GET  /api/kanban/cards', $kanbanApi, 'getCards');
        $this->routes[] = new Route('GET  /api/kanban/card/@id:[0-9]+', $kanbanApi, 'getCard');
        $this->routes[] = new Route('POST /api/kanban/card/create', $kanbanApi, 'createCard');
        $this->routes[] = new Route('POST /api/kanban/card/update', $kanbanApi, 'updateCard');
        $this->routes[] = new Route('POST /api/kanban/card/move', $kanbanApi, 'moveCard');
        $this->routes[] = new Route('POST /api/kanban/card/delete', $kanbanApi, 'deleteCard');
        $this->routes[] = new Route('GET  /api/kanban/card/@id:[0-9]+/history', $kanbanApi, 'getHistory');
        $this->routes[] = new Route('GET  /api/kanban/stats', $kanbanApi, 'getStats');

        return $this->routes;
    }
}
