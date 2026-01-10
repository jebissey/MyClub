import ApiClient from "../../../../../../Common/js/ApiClient.js";
const apiClient = new ApiClient();

export default class CardManager {
    async create(title, detail, cardType) {
        return await apiClient.post('/api/kanban/card/create', { title, detail, cardType });
    }

    async delete(cardId) {
        return await apiClient.post('/api/kanban/card/delete', { id: parseInt(cardId) });
    }

    async history(cardId) {
        return await apiClient.get(`/api/kanban/card/${cardId}/history`);
    }

    async move(cardId, what, remark) {
        return await apiClient.post('/api/kanban/card/move', { idKanbanCard: parseInt(cardId), what, remark });
    }

    async updateStatus(cardId, remark) {
        return await apiClient.post('/api/kanban/cardStatus/update', { idKanbanCardStatus: parseInt(cardId), remark });
    }
}
