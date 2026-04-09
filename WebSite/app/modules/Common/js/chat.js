import { getCurrentPushEndpoint } from "../../User/js/push-subscription.js";
import ApiClient from "./ApiClient.js";

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
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
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

// ── Active users frieze ──────────────────────────────────────────────────────

const REFRESH_INTERVAL_MS = 30_000;

function renderAvatar({ useGravatar, userImg, displayName, timeAgo, browser, os }) {
    const tooltip = `${displayName} — ${timeAgo}\n${browser} / ${os}`;

    if (useGravatar === 'yes' && userImg) {
        return `<img src="${userImg}"
                     class="rounded-circle border border-2 border-success"
                     style="width:48px;height:48px;object-fit:cover;cursor:default"
                     title="${tooltip}"
                     alt="${displayName}">`;
    }

    if (userImg && userImg !== '🤔') {
        return `<span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="font-size:32px;width:48px;height:48px;line-height:48px;text-align:center;background-color:#f0f0f0;cursor:default"
                      title="${tooltip}">${userImg}</span>`;
    }

    return `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                 style="width:48px;height:48px;cursor:default"
                 title="${tooltip}">
                <i class="bi bi-person-circle" style="font-size:3rem"></i>
            </div>`;
}

async function refreshActiveUsers() {
    try {
        const json = await api.get('/api/chat/active-users');

        if (!json.success) return;

        const container = document.getElementById('active-users-list');
        if (!container) return;

        if (json.data.length === 0) {
            container.innerHTML = '<span class="text-muted small">Aucun utilisateur actif</span>';
            return;
        }

        container.innerHTML = json.data.map(renderAvatar).join('');

    } catch (e) {
        console.warn('active-users refresh failed', e);
    }
}

refreshActiveUsers();
setInterval(refreshActiveUsers, REFRESH_INTERVAL_MS);
