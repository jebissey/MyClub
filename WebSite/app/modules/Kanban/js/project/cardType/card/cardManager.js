import ApiClient from "../../../../../../Common/js/apiClient.js";
const apiClient = new ApiClient();

export default class CardManager {
    async create(title, detail, cardType) {
        return await apiClient.post('/api/kanban/card/create', { title, detail, cardType });
    }

    async delete(cardId) {
        return await apiClient.post('/api/kanban/card/delete', { id: parseInt(cardId) });
    }

    async move(cardId, what) {
        return await apiClient.post('/api/kanban/card/move', { idKanbanCard: parseInt(cardId), what });
    }
}
