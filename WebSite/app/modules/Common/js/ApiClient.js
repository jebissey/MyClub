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
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            });

            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await response.text();
                console.error("Non-JSON response:", text);
                throw new Error("Réponse serveur non JSON");
            }

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || "Erreur serveur");
            }

            return data;

        } catch (err) {
            console.error("POST error:", err);
            return { success: false, message: err.message };
        }
    }
}
