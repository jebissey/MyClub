export default class ApiClient {

    async get(endpoint) {
        try {
            const response = await fetch(endpoint);
            return await response.json();
        } catch (err) {
            console.error("GET error:", err);
            return { success: false, error: err.message };
        }
    }

    async getHtml(endpoint) {
        try {
            const response = await fetch(endpoint, {
                headers: {
                    "Accept": "text/html"
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.text();

        } catch (err) {
            console.error("GET HTML error:", err);
            throw err;
        }
    }

    async post(endpoint, payload) {
        try {
            const response = await fetch(endpoint, {
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

    async postFormData(endpoint, formData) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Erreur serveur');
            }

            return data;

        } catch (err) {
            console.error('POST FormData error:', err);
            return { success: false, message: err.message };
        }
    }
}
