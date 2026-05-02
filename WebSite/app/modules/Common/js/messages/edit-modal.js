import ApiClient from '../ApiClient.js';

const api = new ApiClient('');

export function initEditModal() {
    const modalElement    = document.getElementById('edit-message-modal');
    const modal           = new bootstrap.Modal(modalElement);
    const editMessageId   = document.getElementById('edit-message-id');
    const editMessageText = document.getElementById('edit-message-text');
    const editImageWrapper  = document.getElementById('edit-image-wrapper');
    const editImagePreview  = document.getElementById('edit-image-preview');
    const deleteImageBtn    = document.getElementById('delete-image-btn');

    document.querySelectorAll('.edit-message').forEach(btn => {
        btn.addEventListener('click', function () {
            const container = this.closest('[data-message-id]');
            editMessageId.value   = container.dataset.messageId;
            editMessageText.value = container.querySelector('.card-text').textContent.trim();

            const img = container.querySelector('.message-image');
            if (img) {
                editImagePreview.src = img.src;
                editImageWrapper.classList.remove('d-none');
            } else {
                editImagePreview.src = '';
                editImageWrapper.classList.add('d-none');
            }

            modal.show();
        });
    });

    deleteImageBtn.addEventListener('click', async () => {
        if (!confirm('Supprimer l\'image de ce message ?')) return;

        const id   = editMessageId.value;
        const data = await api.post('/api/message/delete-image', { messageId: id });

        if (data.success) {
            document.querySelector(`[data-message-id="${id}"] .message-image`)?.remove();
            editImagePreview.src = '';
            editImageWrapper.classList.add('d-none');
        } else {
            alert('Erreur : ' + data.message);
        }
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