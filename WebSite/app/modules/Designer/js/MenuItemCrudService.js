export default class MenuItemCrudService {
    constructor(api) {
        this.api = api;
    }

    async get(id) {
        return this.api.get(`/api/menuItem/get/${id}`);
    }

    async save(data) {
        return this.api.post('/api/menuItem/save', data);
    }

    async delete(id) {
        return this.api.post(`/api/menuItem/delete/${id}`);
    }

    async updatePositions(positions) {
        return this.api.post('/api/menuitem/updatePositions', { positions });
    }
}