import { SYNC_CONFIG } from '../config.js';

export class ApiManager {
    #abortController = new AbortController();

    constructor(songId, clientId) {
        this.songId = songId;
        this.clientId = clientId;
        this.baseUrl = '/api/karaoke';
        this.retryCount = 0;
    }

    async call(action, extraParams = {}) {
        const params = new URLSearchParams({
            action,
            songId: this.songId,
            clientId: this.clientId,
            ...extraParams
        });

        const url = `${this.baseUrl}?${params}`;
        const timeoutId = setTimeout(() => this.#abortController.abort(), SYNC_CONFIG.REQUEST_TIMEOUT_MS);

        try {
            const response = await fetch(url, { signal: this.#abortController.signal });
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            this.retryCount = 0;
            return data;
        } catch (error) {
            clearTimeout(timeoutId);
            console.error(`API call error [${action}]:`, error);

            if (error.name === 'AbortError') {
                error.message = 'Request timeout';
            }

            this.retryCount++;

            if (this.retryCount >= SYNC_CONFIG.MAX_RETRY_ATTEMPTS) {
                return { success: false, error: 'Max retry attempts reached', critical: true };
            }

            return { success: false, error: error.message };
        }
    }

    async register() { return this.call('register'); }
    async sendHeartbeat() { return this.call('heartbeat'); }
    async getStatus() { return this.call('getStatus'); }
    async startCountdown(delay) { return this.call('startCountdown', { delay }); }
    async disconnect() { return this.call('disconnect'); }
    async stopSession() { return this.call('stopSession'); }
    async cleanup() { return this.call('cleanup'); }

    destroy() {
        this.#abortController.abort();
    }
}