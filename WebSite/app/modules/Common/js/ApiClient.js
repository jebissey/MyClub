export default class ApiClient {
    constructor(baseUrl = "") {
        this.baseUrl = baseUrl;
    }

    async get(endpoint) {
        try {
            const response = await fetch(this.baseUrl + endpoint);
            return await response.json();
        } catch (err) {
            console.error("GET error:", err);
            return { success: false, error: err.message };
        }
    }

    async post(endpoint, payload) {
        try {
            const response = await fetch(this.baseUrl + endpoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            return await response.json();
        } catch (err) {
            console.error("POST error:", err);
            return { success: false, error: err.message };
        }
    }
}
