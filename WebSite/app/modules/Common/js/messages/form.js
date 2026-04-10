import ApiClient from '../ApiClient.js';
import { getCurrentPushEndpoint } from '../../../User/js/push-subscription.js';

const api = new ApiClient('');

export function initMessageForm() {
    const form = document.getElementById('new-message-form');
    const messageText = document.getElementById('message-text');
    const articleId = document.getElementById('article-id').value;
    const eventId = document.getElementById('event-id').value;
    const groupId = document.getElementById('group-id').value;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!messageText.value.trim()) return;

        const senderEndpoint = await getCurrentPushEndpoint();
        const data = await api.post('/api/message/add', {
            articleId,
            eventId,
            groupId,
            text: messageText.value,
            senderEndpoint,
        });

        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || "Impossible d'envoyer le message"));
        }
    });
}