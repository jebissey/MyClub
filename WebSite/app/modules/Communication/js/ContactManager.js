import ApiClient from '/app/modules/Common/js/ApiClient.js';

const api = new ApiClient();

export default class ContactManager {
    #input  = null;
    #status = null;

    constructor() {
        this.#input  = document.getElementById('contact-email-input');
        this.#status = document.getElementById('contact-email-status');
    }

    bindEvents() {
        this.#input?.addEventListener('blur', () => this.#onBlur());
    }

    // ── Private ─────────────────────────────────────────────────────────────────

    async #onBlur() {
        const value = this.#input.value.trim();

        if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            this.#setStatus('✗', 'text-danger');
            return;
        }

        try {
            const response = await api.post('/api/communication/contact-email', { value });
            this.#setStatus(response.success ? '✓' : '✗', response.success ? 'text-success' : 'text-danger');
        } catch {
            this.#setStatus('✗', 'text-danger');
        }

        setTimeout(() => this.#setStatus('', ''), 3000);
    }

    #setStatus(icon, colorClass) {
        this.#status.textContent = icon;
        this.#status.className   = `input-group-text px-2 ${colorClass}`.trim();
    }
}