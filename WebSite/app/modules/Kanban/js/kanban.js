// Mapping des transitions de statut
const statusTransitions = {
    '💡': {
        '💡': null,
        '☑️': 'MovedFromBacklogToSelected',
        '🔧': 'MovedFromBacklogToInProgress',
        '🏁': 'MovedFromBacklogToDone'
    },
    '☑️': {
        '💡': 'MovedFromSelectedToBacklog',
        '☑️': null,
        '🔧': 'MovedFromSelectedToInProgress',
        '🏁': 'MovedFromSelectedToDone'
    },
    '🔧': {
        '💡': 'MovedFromInProgressToBacklog',
        '☑️': 'MovedFromInProgressToSelected',
        '🔧': null,
        '🏁': 'MovedFromInProgressToDone'
    },
    '🏁': {
        '💡': 'MovedFromDoneToBacklog',
        '☑️': 'MovedFromDoneToSelected',
        '🔧': 'MovedFromDoneToInProgress',
        '🏁': null
    }
};

document.addEventListener('DOMContentLoaded', function () {
    initializeKanban();
});

function initializeKanban() {
    // Drag & Drop pour les cartes
    const cards = document.querySelectorAll('.kanban-card');
    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    // Drop zones
    const columns = document.querySelectorAll('.kanban-cards');
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
        column.addEventListener('dragleave', handleDragLeave);
    });

    // Bouton pour ajouter une nouvelle carte
    document.getElementById('saveNewCard').addEventListener('click', createNewCard);

    // Boutons d'édition
    document.querySelectorAll('.edit-card').forEach(btn => {
        btn.addEventListener('click', function () {
            const cardId = this.getAttribute('data-id');
            openEditModal(cardId);
        });
    });

    // Boutons de suppression
    document.querySelectorAll('.delete-card').forEach(btn => {
        btn.addEventListener('click', function () {
            const cardId = this.getAttribute('data-id');
            deleteCard(cardId);
        });
    });

    // Sauvegarder l'édition
    document.getElementById('saveEditCard').addEventListener('click', saveEditedCard);
}

let draggedCard = null;

function handleDragStart(e) {
    draggedCard = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
    return false;
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    this.classList.remove('drag-over');

    if (draggedCard) {
        const oldStatus = draggedCard.getAttribute('data-status');
        const newStatus = this.getAttribute('data-status');
        const cardId = draggedCard.getAttribute('data-id');

        if (oldStatus !== newStatus) {
            const changeType = statusTransitions[oldStatus][newStatus];

            if (changeType) {
                // Déplacer visuellement la carte
                this.appendChild(draggedCard);
                draggedCard.setAttribute('data-status', newStatus);

                // Enregistrer le changement côté serveur
                moveCardToStatus(cardId, newStatus, changeType);
            }
        }
    }

    return false;
}

function createNewCard() {
    const title = document.getElementById('cardTitle').value.trim();
    const detail = document.getElementById('cardDetail').value.trim();

    if (!title) {
        alert('Le titre est obligatoire');
        return;
    }

    fetch('/api/kanban/card/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title, detail })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page pour afficher la nouvelle carte
                location.reload();
            } else {
                alert('Erreur : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la création de la carte');
        });
}

function moveCardToStatus(cardId, newStatus, changeType) {
    fetch('/api/kanban/card/move', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: parseInt(cardId),
            status: newStatus,
            changeType: changeType,
            remark: ''
        })
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Erreur lors du déplacement : ' + (data.error || 'Erreur inconnue'));
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors du déplacement de la carte');
            location.reload();
        });
}

function openEditModal(cardId) {
    const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
    const title = card.querySelector('.kanban-card-title').textContent;
    const detail = card.querySelector('.kanban-card-detail').textContent;

    document.getElementById('editCardId').value = cardId;
    document.getElementById('editCardTitle').value = title;
    document.getElementById('editCardDetail').value = detail;

    const modal = new bootstrap.Modal(document.getElementById('editCardModal'));
    modal.show();
}

function saveEditedCard() {
    const cardId = document.getElementById('editCardId').value;
    const title = document.getElementById('editCardTitle').value.trim();
    const detail = document.getElementById('editCardDetail').value.trim();

    if (!title) {
        alert('Le titre est obligatoire');
        return;
    }

    fetch('/api/kanban/card/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: parseInt(cardId),
            title,
            detail
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour visuellement
                const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
                card.querySelector('.kanban-card-title').textContent = title;
                card.querySelector('.kanban-card-detail').textContent = detail;

                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editCardModal'));
                modal.hide();
            } else {
                alert('Erreur : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la mise à jour de la carte');
        });
}

function deleteCard(cardId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) {
        return;
    }

    fetch('/api/kanban/card/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: parseInt(cardId) })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprimer visuellement la carte
                const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
                card.remove();
            } else {
                alert('Erreur : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la suppression de la carte');
        });
}

function refreshStats() {
    fetch('/api/kanban/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                // Mettre à jour l'affichage des stats si présent
                console.log('Stats:', data.stats);
            }
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
        });
}