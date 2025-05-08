document.addEventListener('DOMContentLoaded', function () {
    const chatContainer = document.getElementById('chat-container');
    const newMessageForm = document.getElementById('new-message-form');
    const messageText = document.getElementById('message-text');
    const eventId = document.getElementById('event-id').value;
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

    newMessageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!messageText.value.trim()) {
            return;
        }

        fetch('/api/message/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                eventId: eventId,
                text: messageText.value
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = data.data;

                    const messageContainer = document.createElement('div');
                    messageContainer.className = 'message-container mb-3 message-mine';
                    messageContainer.dataset.messageId = message.Id;
                    messageContainer.dataset.authorId = message.PersonId;

                    const avatarSrc = '{if $currentPerson["UseGravatar"] == "yes"}https://www.gravatar.com/avatar/{md5(strtolower(trim($currentPerson["Email"])))}' +
                        '{elseif $currentPerson["Avatar"]}{$currentPerson["Avatar"]}' +
                        '{else}/app/images/emojiPensif.png{/if}';

                    const userName = '{if $currentPerson["NickName"]}{$currentPerson["NickName"]}{else}{$currentPerson["FirstName"]} {$currentPerson["LastName"]}{/if}';

                    messageContainer.innerHTML = `
                    <div class="d-flex flex-row-reverse">
                        <div class="avatar ml-2 mr-0">
                            <img src="${avatarSrc}" class="rounded-circle" width="40" height="40" alt="Avatar">
                        </div>
                        <div class="message-content bg-primary text-white p-2 rounded">
                            <div class="message-header mb-1">
                                <strong>${userName}</strong>
                                <small class="text-muted">
                                    <i class="edit-message fa fa-edit ml-2" title="Modifier"></i>
                                </small>
                            </div>
                            <div class="message-text">
                                ${message.Text}
                            </div>
                        </div>
                    </div>
                `;

                    chatContainer.appendChild(messageContainer);
                    messageText.value = '';

                    scrollToBottom();

                    const editButton = messageContainer.querySelector('.edit-message');
                    if (editButton) {
                        editButton.addEventListener('click', function () {
                            openEditModal(message.Id, message.Text);
                        });
                    }
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de l\'envoi du message');
            });
    });

    document.querySelectorAll('.edit-message').forEach(button => {
        button.addEventListener('click', function () {
            const messageContainer = this.closest('.message-container');
            const messageId = messageContainer.dataset.messageId;
            const messageText = messageContainer.querySelector('.message-text').textContent.trim();

            openEditModal(messageId, messageText);
        });
    });

    function openEditModal(messageId, text) {
        editMessageId.value = messageId;
        editMessageText.value = text;
        editMessageModal.modal.show;
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
                    // Update message in the DOM
                    const messageContainer = document.querySelector(`.message-container[data-message-id="${messageId}"]`);
                    if (messageContainer) {
                        const messageTextElement = messageContainer.querySelector('.message-text');
                        if (messageTextElement) {
                            messageTextElement.textContent = newText;
                        }
                    }

                    editMessageModal.modal.hide;
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la modification du message');
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
                    const messageContainer = document.querySelector(`.message-container[data-message-id="${messageId}"]`);
                    if (messageContainer) {
                        messageContainer.remove();
                    }

                    editMessageModal.modal('hide');
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la suppression du message');
            });
    });
});
