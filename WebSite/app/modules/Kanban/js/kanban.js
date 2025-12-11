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
    handleProjectSelection();

    const saveProjectBtn = document.getElementById('saveNewProject');
    if (saveProjectBtn) {
        saveProjectBtn.addEventListener('click', createNewProject);
    }

    // Gestionnaire pour le bouton d'édition du projet
    const editProjectBtn = document.getElementById('editProjectBtn');
    if (editProjectBtn) {
        editProjectBtn.addEventListener('click', function () {
            const projectId = document.getElementById('kanbanProjectSelect').value;
            if (projectId) {
                loadProjectForEdit(projectId);
            }
        });
    }

    const saveEditProjectBtn = document.getElementById('saveEditProject');
    if (saveEditProjectBtn) {
        saveEditProjectBtn.addEventListener('click', saveEditedProject);
    }
    const deleteProjectBtn = document.getElementById('deleteProjectBtn');
    if (deleteProjectBtn) {
        deleteProjectBtn.addEventListener('click', deleteProject);
    }
    const addCardTypeBtn = document.getElementById('addCardTypeBtn');
    if (addCardTypeBtn) {
        addCardTypeBtn.addEventListener('click', showNewCardTypeForm);
    }
    const saveNewCardTypeBtn = document.getElementById('saveNewCardType');
    if (saveNewCardTypeBtn) {
        saveNewCardTypeBtn.addEventListener('click', createNewCardType);
    }
    const cancelNewCardTypeBtn = document.getElementById('cancelNewCardType');
    if (cancelNewCardTypeBtn) {
        cancelNewCardTypeBtn.addEventListener('click', hideNewCardTypeForm);
    }
});

function handleProjectSelection() {
    const select = document.getElementById("kanbanProjectSelect");
    const addProjectBtn = document.getElementById("addProjectBtn");
    const addCardBtn = document.getElementById("addCardBtn");
    const editProjectBtn = document.getElementById("editProjectBtn");
    const kanbanBoard = document.getElementById("kanbanBoard");
    const statsContainer = document.getElementById("statsContainer");

    select.addEventListener("change", () => {
        if (select.value === "") {
            addProjectBtn.classList.remove("d-none");
            addCardBtn.classList.add("d-none");
            editProjectBtn.classList.add("d-none");
            kanbanBoard.classList.add("d-none");
            statsContainer.classList.add("d-none");
        } else {
            // Un projet est sélectionné
            addProjectBtn.classList.add("d-none");
            addCardBtn.classList.remove("d-none");
            editProjectBtn.classList.remove("d-none");
            kanbanBoard.classList.remove("d-none");
            statsContainer.classList.remove("d-none");

            loadProjectCards(select.value);
            loadCardTypesForSelect(select.value);
        }
    });
}

function createNewProject() {
    const title = document.getElementById('projectTitle').value.trim();
    const detail = document.getElementById('projectDescription').value.trim();

    if (!title) {
        alert('Le titre du projet est obligatoire');
        return;
    }
    fetch('/api/kanban/project/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, detail })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Erreur : ' + (data.error || 'Erreur inconnue'));
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la création du projet');
        });
}

function loadProjectForEdit(projectId) {
    fetch(`/api/kanban/project/${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editProjectId').value = data.project.Id;
                document.getElementById('editProjectTitle').value = data.project.Title;
                document.getElementById('editProjectDescription').value = data.project.Detail || '';

                loadCardTypes(projectId);
            }
        })
        .catch(error => {
            console.error('Error loading project:', error);
            alert('Erreur lors du chargement du projet');
        });
}

function loadCardTypes(projectId) {
    fetch(`/api/kanban/project/${projectId}/cardTypes`)
        .then(response => response.json())
        .then(data => {
            if (data.success) displayCardTypes(data.cardTypes);
        })
        .catch(error => {
            console.error('Error loading card types:', error);
        });
}

function displayCardTypes(cardTypes) {
    const container = document.getElementById('cardTypesList');
    container.innerHTML = '';

    if (cardTypes.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun type de carte défini</p>';
        return;
    }

    cardTypes.forEach(type => {
        const typeElement = document.createElement('div');
        typeElement.className = 'card-type-item';
        typeElement.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div>
                        <span class="fw-bold">${type.Label}</span>
                        ${type.Detail ? `<span class="text-muted small"> - ${type.Detail}</span>` : ''}
                    </div>
                </div>
                <button class="btn btn-sm btn-danger delete-card-type" 
                        data-id="${type.Id}" 
                        title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        typeElement.querySelector('.delete-card-type').addEventListener('click', function () {
            deleteCardType(type.Id);
        });
        container.appendChild(typeElement);
    });
}

function showNewCardTypeForm() {
    document.getElementById('newCardTypeForm').classList.remove('d-none');
    document.getElementById('addCardTypeBtn').disabled = true;
}

function hideNewCardTypeForm() {
    document.getElementById('newCardTypeForm').classList.add('d-none');
    document.getElementById('newCardTypeLabel').value = '';
    document.getElementById('newCardTypeDetail').value = '';
    document.getElementById('addCardTypeBtn').disabled = false;
}

function createNewCardType() {
    const projectId = document.getElementById('editProjectId').value;
    const label = document.getElementById('newCardTypeLabel').value.trim();
    const detail = document.getElementById('newCardTypeDetail').value.trim();

    if (!label) {
        alert('Le label est obligatoire');
        return;
    }
    fetch('/api/kanban/cardType/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            projectId: parseInt(projectId),
            label,
            detail
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hideNewCardTypeForm();
                loadCardTypes(projectId);
            } else alert('Erreur : ' + (data.error || 'Erreur inconnue'));
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la création du type de carte');
        });
}

function deleteCardType(cardTypeId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce type de carte ?')) {
        return;
    }

    fetch('/api/kanban/cardType/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(cardTypeId) })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const projectId = document.getElementById('editProjectId').value;
                loadCardTypes(projectId);
            } else {
                alert('Erreur : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la suppression du type de carte');
        });
}

function saveEditedProject() {
    const projectId = document.getElementById('editProjectId').value;
    const title = document.getElementById('editProjectTitle').value.trim();
    const detail = document.getElementById('editProjectDescription').value.trim();

    if (!title) {
        alert('Le titre est obligatoire');
        return;
    }

    fetch('/api/kanban/project/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: parseInt(projectId),
            title,
            detail
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le select
                const select = document.getElementById('kanbanProjectSelect');
                const option = select.querySelector(`option[value="${projectId}"]`);
                if (option) {
                    option.textContent = title;
                }

                // Fermer la modale
                const modal = bootstrap.Modal.getInstance(document.getElementById('editProjectModal'));
                modal.hide();
            } else {
                alert('Erreur : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la mise à jour du projet');
        });
}

function deleteProject() {
    const projectId = document.getElementById('editProjectId').value;
    const projectTitle = document.getElementById('editProjectTitle').value;

    if (!confirm(`Êtes-vous sûr de vouloir supprimer le projet "${projectTitle}" ?\n\nToutes les cartes associées seront également supprimées.`)) {
        return;
    }

    fetch('/api/kanban/project/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(projectId) })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur : ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la suppression du projet');
        });
}

function loadProjectCards(projectId) {
    fetch(`/api/kanban/project/${projectId}/cards`)
        .then(response => response.json())
        .then(data => {
            if (data.success) updateKanbanBoard(data.cards);
        })
        .catch(error => {
            console.error('Error loading cards:', error);
        });
}

function updateKanbanBoard(cards) {
    document.querySelectorAll('.kanban-cards').forEach(column => {
        column.innerHTML = '';
    });
    cards.forEach(card => {
        const column = document.querySelector(`.kanban-cards[data-status="${card.CurrentStatus}"]`);
        if (column) {
            const cardElement = createCardElement(card);
            column.appendChild(cardElement);
        }
    });
    initializeDragAndDrop();
}

function createCardElement(card) {
    const cardDiv = document.createElement('div');
    cardDiv.className = 'kanban-card';
    cardDiv.draggable = true;
    cardDiv.setAttribute('data-id', card.Id);
    cardDiv.setAttribute('data-status', card.CurrentStatus);

    const titleDiv = document.createElement('div');
    titleDiv.className = 'kanban-card-title';
    titleDiv.textContent = card.Title;
    cardDiv.appendChild(titleDiv);

    if (card.Detail) {
        const detailDiv = document.createElement('div');
        detailDiv.className = 'kanban-card-detail';
        detailDiv.textContent = card.Detail;
        cardDiv.appendChild(detailDiv);
    }

    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'mt-2 d-flex gap-1';
    actionsDiv.innerHTML = `
        <button class="btn btn-sm btn-warning edit-card" data-id="${card.Id}" title="Éditer">
            <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-danger delete-card" data-id="${card.Id}" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>
    `;
    cardDiv.appendChild(actionsDiv);

    // Ajouter les événements
    cardDiv.addEventListener('dragstart', handleDragStart);
    cardDiv.addEventListener('dragend', handleDragEnd);

    actionsDiv.querySelector('.edit-card').addEventListener('click', function () {
        openEditModal(card.Id);
    });

    actionsDiv.querySelector('.delete-card').addEventListener('click', function () {
        deleteCard(card.Id);
    });

    return cardDiv;
}

function initializeKanban() {
    initializeDragAndDrop();

    document.getElementById('saveNewCard').addEventListener('click', createNewCard);
    document.querySelectorAll('.edit-card').forEach(btn => {
        btn.addEventListener('click', function () {
            const cardId = this.getAttribute('data-id');
            openEditModal(cardId);
        });
    });
    document.querySelectorAll('.delete-card').forEach(btn => {
        btn.addEventListener('click', function () {
            const cardId = this.getAttribute('data-id');
            deleteCard(cardId);
        });
    });
    document.getElementById('saveEditCard').addEventListener('click', saveEditedCard);
}

function initializeDragAndDrop() {
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
    const cardType = document.getElementById('cardType').value;

    if (!title) {
        alert('Le titre est obligatoire');
        return;
    }
    if (!cardType) {
        alert('Veuillez sélectionner un type de carte');
        return;
    }

    fetch('/api/kanban/card/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title, detail, cardType })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) location.reload();
            else              alert('Erreur : ' + (data.error || 'Erreur inconnue'));
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
    const detailElement = card.querySelector('.kanban-card-detail');
    const detail = detailElement ? detailElement.textContent : '';

    document.getElementById('editCardId').value = cardId;
    document.getElementById('editCardTitle').value = title;
    document.getElementById('editCardDetail').value = detail;
    document.getElementById('editCardType').value = card.IdKanbanCardType;

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
            detail,
            typeId: parseInt(document.getElementById('editCardType').value)
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour visuellement
                const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
                card.querySelector('.kanban-card-title').textContent = title;

                const detailElement = card.querySelector('.kanban-card-detail');
                if (detailElement) {
                    detailElement.textContent = detail;
                } else if (detail) {
                    // Créer l'élément detail s'il n'existe pas
                    const newDetailDiv = document.createElement('div');
                    newDetailDiv.className = 'kanban-card-detail';
                    newDetailDiv.textContent = detail;
                    card.querySelector('.kanban-card-title').after(newDetailDiv);
                }

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
    const projectId = document.getElementById('kanbanProjectSelect').value;
    if (!projectId) return;

    fetch(`/api/kanban/project/${projectId}/stats`)
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

async function loadCardTypesForSelect(projectId) {
    const r = await fetch(`/api/kanban/project/${projectId}/cardTypes`);
    const data = await r.json();
    if (!data.success) return;
    const addSelect = document.getElementById('cardType');
    const editSelect = document.getElementById('editCardType');
    addSelect.innerHTML = '<option value="">Choisir un type</option>';
    editSelect.innerHTML = '<option value="">Choisir un type</option>';
    data.cardTypes.forEach(t => {
        const option = `<option value="${t.Id}">${t.Label}</option>`;
        addSelect.insertAdjacentHTML('beforeend', option);
        editSelect.insertAdjacentHTML('beforeend', option);
    });
}
