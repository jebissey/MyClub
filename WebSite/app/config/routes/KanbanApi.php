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

        $this->routes[] = new Route('GET  /api/kanban/cards', $kanbanApi, 'getCards');
        $this->routes[] = new Route('GET  /api/kanban/card/@id:[0-9]+/history', $kanbanApi, 'getHistory');
        $this->routes[] = new Route('POST /api/kanban/card/create', $kanbanApi, 'createCard');
        $this->routes[] = new Route('POST /api/kanban/card/delete', $kanbanApi, 'deleteCard');
        $this->routes[] = new Route('POST /api/kanban/card/move', $kanbanApi, 'moveCard');
        $this->routes[] = new Route('POST /api/kanban/card/update', $kanbanApi, 'updateCard');
        $this->routes[] = new Route('POST /api/kanban/cardStatus/update', $kanbanApi, 'updateCardStatus');
        $this->routes[] = new Route('POST /api/kanban/cardType/create', $kanbanApi, 'createCardType');
        $this->routes[] = new Route('POST /api/kanban/cardType/delete', $kanbanApi, 'deleteCardType');
        $this->routes[] = new Route('GET  /api/kanban/project/@id:[0-9]+', $kanbanApi, 'getProject');
        $this->routes[] = new Route('GET  /api/kanban/project/@id:[0-9]+/cards', $kanbanApi, 'getProjectCards');
        $this->routes[] = new Route('GET  /api/kanban/project/@id:[0-9]+/cardTypes', $kanbanApi, 'getProjectCardTypes');
        $this->routes[] = new Route('POST /api/kanban/project/create', $kanbanApi, 'createProject');
        $this->routes[] = new Route('POST /api/kanban/project/delete', $kanbanApi, 'deleteProject');
        $this->routes[] = new Route('POST /api/kanban/project/update', $kanbanApi, 'updateProject');

        return $this->routes;
    }
}
