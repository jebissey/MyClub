import { getCurrentPushEndpoint } from "../../User/js/push-subscription.js";
import ApiClient from "../../Common/js/apiClient.js";

const api = new ApiClient("");

document.addEventListener('DOMContentLoaded', function () {
    const chatContainer = document.getElementById('chat-container');
    const newMessageForm = document.getElementById('new-message-form');
    const messageText = document.getElementById('message-text');
    const articleId = document.getElementById('article-id').value;
    const eventId = document.getElementById('event-id').value;
    const groupId = document.getElementById('group-id').value;
    const editMessageModalElement = document.getElementById('edit-message-modal');
    const editMessageModal = new bootstrap.Modal(editMessageModalElement);
    const editMessageId = document.getElementById('edit-message-id');
    const editMessageText = document.getElementById('edit-message-text');
    const saveEditMessageBtn = document.getElementById('save-edit-message-btn');
    const deleteMessageBtn = document.getElementById('delete-message-btn');

    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    scrollToBottom();

    newMessageForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!messageText.value.trim()) {
            return;
        }
        const senderEndpoint = await getCurrentPushEndpoint();
        const data = await api.post("/api/message/add", {
            articleId: articleId,
            eventId: eventId,
            groupId: groupId,
            text: messageText.value,
            senderEndpoint: senderEndpoint
        });
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Impossible d\'envoyer le message'));
        }
    });

    document.querySelectorAll('.edit-message').forEach(button => {
        button.addEventListener('click', function () {
            const messageContainer = this.closest('[data-message-id]');
            const messageId = messageContainer.dataset.messageId;
            const messageText = messageContainer.querySelector('.card-text').textContent.trim();

            openEditModal(messageId, messageText);
        });
    });

    function openEditModal(messageId, text) {
        editMessageId.value = messageId;
        editMessageText.value = text;
        editMessageModal.show();
    }

    saveEditMessageBtn.addEventListener('click', function () {
        const messageId = editMessageId.value;
        const newText = editMessageText.value.trim();

        if (!newText) {
            return;
        }

        fetch('/api/message/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                messageId: messageId,
                text: newText
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messageContainer = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageContainer) {
                        const messageTextElement = messageContainer.querySelector('.card-text');
                        if (messageTextElement) {
                            messageTextElement.textContent = newText;
                        }
                    }
                    editMessageModal.hide();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                alert('Une erreur est survenue lors de la modification du message : ' + error);
            });
    });

    deleteMessageBtn.addEventListener('click', function () {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
            return;
        }
        const messageId = editMessageId.value;
        fetch('/api/message/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                messageId: messageId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messageContainer = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageContainer) {
                        messageContainer.remove();
                    }

                    editMessageModal.hide();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                alert('Une erreur est survenue lors de la suppression du message : ' + error);
            });
    });
});
