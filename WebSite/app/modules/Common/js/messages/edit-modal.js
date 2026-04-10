import ApiClient from '../ApiClient.js';

const api = new ApiClient('');

export function initEditModal() {
    const modalElement    = document.getElementById('edit-message-modal');
    const modal           = new bootstrap.Modal(modalElement);
    const editMessageId   = document.getElementById('edit-message-id');
    const editMessageText = document.getElementById('edit-message-text');

    document.querySelectorAll('.edit-message').forEach(btn => {
        btn.addEventListener('click', function () {
            const container = this.closest('[data-message-id]');
            editMessageId.value   = container.dataset.messageId;
            editMessageText.value = container.querySelector('.card-text').textContent.trim();
            modal.show();
        });
    });

    document.getElementById('save-edit-message-btn').addEventListener('click', async () => {
        const id   = editMessageId.value;
        const text = editMessageText.value.trim();
        if (!text) return;

        const data = await api.post('/api/message/update', { messageId: id, text });
        if (data.success) {
            document.querySelector(`[data-message-id="${id}"] .card-text`).textContent = text;
            modal.hide();
        } else {
            alert('Erreur : ' + data.message);
        }
    });

    document.getElementById('delete-message-btn').addEventListener('click', async () => {
        if (!confirm('Supprimer ce message ?')) return;

        const id   = editMessageId.value;
        const data = await api.post('/api/message/delete', { messageId: id });
        if (data.success) {
            document.querySelector(`[data-message-id="${id}"]`)?.remove();
            modal.hide();
        } else {
            alert('Erreur : ' + data.message);
        }
    });
}