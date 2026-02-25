import ApiClient from "../../../../Common/js/ApiClient.js";
const apiClient = new ApiClient();

export default class CardTypeManager {
    async load(projectId) {
        return await apiClient.get(`/api/kanban/project/${projectId}/cardTypes`);
    }

    async create(projectId, label, detail, color) {
        return await apiClient.post('/api/kanban/cardType/create', { projectId, label, detail, color });
    }

    async delete(cardTypeId) {
        return await apiClient.post('/api/kanban/cardType/delete', { id: parseInt(cardTypeId) });
    }

    async update(cardTypeId, label, detail, color) {
        return await apiClient.post('/api/kanban/cardType/update', { id: parseInt(cardTypeId), label, detail, color });
    }
}
