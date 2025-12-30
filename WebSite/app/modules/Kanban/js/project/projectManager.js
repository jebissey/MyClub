import ApiClient from "../../../Common/js/apiClient.js";
const apiClient = new ApiClient();

export default class ProjectManager {
    async create(title, detail) {
        return await apiClient.post('/api/kanban/project/create', { title, detail });
    }

    async delete(id) {
        return await apiClient.post('/api/kanban/project/delete', { id });
    }

    async getCards(projectId, ct = '', title = '', detail = '') {
        const params = new URLSearchParams();
        if (ct !== '') params.append('ct', ct);
        if (title !== '') params.append('title', title);
        if (detail !== '') params.append('detail', detail);

        return await apiClient.get(`/api/kanban/project/${projectId}/cards?${params.toString()}`);
    }

    async load(projectId) {
        return await apiClient.get(`/api/kanban/project/${projectId}`);
    }

    async update(id, title, detail) {
        return await apiClient.post('/api/kanban/project/update', { id, title, detail });
    }
}
