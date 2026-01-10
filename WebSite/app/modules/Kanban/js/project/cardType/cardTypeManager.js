import ApiClient from "../../../../Common/js/ApiClient.js";
const apiClient = new ApiClient();

export default class CardTypeManager {
    async load(projectId) {
        return await apiClient.get(`/api/kanban/project/${projectId}/cardTypes`);
    }

    async create(projectId, label, detail) {
        return await apiClient.post('/api/kanban/cardType/create', { projectId, label, detail });
    }

    async delete(cardTypeId) {
        return await apiClient.post('/api/kanban/cardType/delete', { id: parseInt(cardTypeId) });
    }
}
