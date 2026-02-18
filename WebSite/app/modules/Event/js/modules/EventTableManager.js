import EventFormManager from './EventFormManager.js';

export default class EventTableManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.eventForm = new EventFormManager(this.api);
        this._init();
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    _init() {
        document.getElementById('createEventBtn')
            ?.addEventListener('click', () => {
                this.eventForm.openCreateModal();
            });

        document.querySelectorAll('.edit-btn').forEach(btn =>
            btn.addEventListener('click', e => this.handleEdit(e))
        );

        document.querySelectorAll('.delete-btn').forEach(btn =>
            btn.addEventListener('click', e => this.handleDelete(e))
        );

        document.querySelectorAll('.duplicate-btn').forEach(btn =>
            btn.addEventListener('click', e => this.handleDuplicate(e))
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Extracts the event ID from the onclick attribute of a table row.
     * @param {HTMLElement} row
     * @returns {string|null}
     */
    _getEventIdFromRow(row) {
        const onclick = row?.getAttribute('onclick') ?? '';
        const match = onclick.match(/\/event\/(\d+)/);
        return match ? match[1] : null;
    }

    /**
     * Displays an error notification via a Bootstrap toast.
     * Creates the toast container if it does not yet exist in the DOM.
     * @param {string} message
     */
    showError(message) {
        this._showToast(message, 'danger');
    }

    /**
     * Displays a success notification via a Bootstrap toast.
     * @param {string} message
     */
    showSuccess(message) {
        this._showToast(message, 'success');
    }

    /**
     * @private
     * @param {string} message
     * @param {'success'|'danger'|'warning'|'info'} type
     */
    _showToast(message, type = 'info') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1100';
            document.body.appendChild(container);
        }

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;

        container.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();

        // Clean up the DOM node once the toast has hidden
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    /**
     * Called after a successful duplication.
     * Dispatches a custom event so other modules can react,
     * then reloads the page.
     */
    onDuplicateSuccess() {
        document.dispatchEvent(new CustomEvent('event:duplicated'));
        window.location.reload();
    }

    // ─── Handlers ────────────────────────────────────────────────────────────

    async handleEdit(e) {
        e.stopPropagation();

        const row = e.target.closest('tr');
        const eventId = this._getEventIdFromRow(row);

        if (!eventId) {
            this.showError('Could not identify the event to edit.');
            return;
        }

        try {
            const response = await this.api.get(`/api/event/${eventId}`);

            if (response.success && response.data?.event && response.data?.attributes) {
                await this.eventForm.openUpdateModal(response.data.event, response.data.attributes);
            } else {
                this.showError('Error retrieving event details.');
            }
        } catch (err) {
            console.error('[handleEdit]', err);
            this.showError('An unexpected error occurred.');
        }
    }

    async handleDelete(e) {
        e.stopPropagation();

        const row = e.target.closest('tr');
        const eventId = this._getEventIdFromRow(row);

        if (!eventId) {
            this.showError('Could not identify the event to delete.');
            return;
        }

        if (!confirm('Are you sure you want to delete this event?')) return;

        try {
            const result = await this.api.post(`/api/event/delete/${eventId}`, {});

            if (result.success) {
                window.location.reload();
            } else {
                this.showError('Error while deleting: ' + (result.message || 'Unknown error'));
            }
        } catch (err) {
            console.error('[handleDelete]', err);
            this.showError('An unexpected error occurred.');
        }
    }

    handleDuplicate(e) {
        e.stopPropagation();

        const triggerBtn = e.target.closest('.duplicate-btn');
        if (!triggerBtn) return;

        const eventId = triggerBtn.dataset.id;
        if (!eventId) {
            this.showError('Could not identify the event to duplicate.');
            return;
        }

        const modalEl = document.getElementById('duplicateModal');
        if (!modalEl) {
            console.error('[handleDuplicate] #duplicateModal not found in the DOM.');
            return;
        }

        const duplicateModal = new bootstrap.Modal(modalEl);

        duplicateModal.show();
        modalEl.querySelectorAll("input[name='duplicateChoice']")
            .forEach(i => (i.checked = false));

        // Replace the node to purge any previously attached listeners
        const confirmBtn = document.getElementById('confirmDuplicate');
        if (!confirmBtn) {
            console.error('[handleDuplicate] #confirmDuplicate not found in the DOM.');
            return;
        }

        const freshBtn = confirmBtn.cloneNode(true);
        confirmBtn.replaceWith(freshBtn);

        freshBtn.addEventListener('click', async () => {
            const choice = modalEl.querySelector("input[name='duplicateChoice']:checked");

            if (!choice) {
                this.showError('Please select an option.');
                return;
            }

            const mode = choice.value;

            freshBtn.disabled = true;
            try {
                const result = await this.api.post(`/api/event/duplicate/${eventId}?mode=${mode}`, {});

                if (result.success) {
                    duplicateModal.hide();
                    this.onDuplicateSuccess();
                } else {
                    this.showError('Error: ' + (result.message || 'Unknown error'));
                }
            } catch (err) {
                console.error('[handleDuplicate]', err);
                this.showError('An unexpected error occurred.');
            } finally {
                freshBtn.disabled = false;
            }
        }, { once: true });
    }
}