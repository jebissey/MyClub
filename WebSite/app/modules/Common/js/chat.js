import { initMessageForm } from './messages/form.js';
import { initEditModal } from './messages/edit-modal.js';
import { startActiveUsersPolling } from './active-users/refresher.js';

document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chat-container');
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;

    initMessageForm();
    initEditModal();
    startActiveUsersPolling();
});