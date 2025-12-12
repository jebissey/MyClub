import ApiClient from "./apiClient.js";
const apiClient = new ApiClient();

export default class CardManager {
    async load(projectId) {
        return await apiClient.get(`/api/kanban/project/${projectId}/cardTypes`);
    }

    async create(projectId, label, detail) {
        return await apiClient.post('/api/kanban/cardType/create', { projectId, label, detail });
    }

    async delete(cardTypeId) {
        return await apiClient.post('/api/kanban/cardType/delete', { id: parseInt(cardTypeId) });
    }


/*
     $this->routes[] = new Route('GET  /api/kanban/cards', $kanbanApi, 'getCards');
        $this->routes[] = new Route('GET  /api/kanban/card/@id:[0-9]+', $kanbanApi, 'getCard');
        $this->routes[] = new Route('GET  /api/kanban/card/@id:[0-9]+/history', $kanbanApi, 'getHistory');
        $this->routes[] = new Route('POST /api/kanban/card/create', $kanbanApi, 'createCard');
        $this->routes[] = new Route('POST /api/kanban/card/delete', $kanbanApi, 'deleteCard');
        $this->routes[] = new Route('POST /api/kanban/card/move', $kanbanApi, 'moveCard');
        $this->routes[] = new Route('POST /api/kanban/card/update', $kanbanApi, 'updateCard');

        $this->routes[] = new Route('GET  /api/kanban/project/@id:[0-9]+/cards', $kanbanApi, 'getProjectCards');*/

}
