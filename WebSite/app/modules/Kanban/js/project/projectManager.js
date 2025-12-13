import ApiClient from "../../../Common/js/apiClient.js";
const apiClient = new ApiClient();

export default class ProjectManager {
    async create(title, detail) {
        return await apiClient.post('/api/kanban/project/create', { title, detail });
    }

    async delete(id) {
        return await apiClient.post('/api/kanban/project/delete', { id });
    }

    async getCards(projectId) {
        return await apiClient.get(`/api/kanban/project/${projectId}/cards`);
    }

    async load(projectId) {
        return await apiClient.get(`/api/kanban/project/${projectId}`);
    }

    async update(id, title, detail) {
        return await apiClient.post('/api/kanban/project/update', { id, title, detail });
    }
}
