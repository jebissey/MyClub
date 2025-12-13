import ApiClient from "../../Common/js/apiClient.js";
const apiClient = new ApiClient();

export default class KanbanBoard {
    constructor(statusTransitions) {
        this.statusTransitions = statusTransitions;
        this.draggedCard = null;
    }

    init() {
        this.initDragAndDrop();
        document.getElementById('saveNewCard')?.addEventListener('click', () => this.createNewCard());
        document.getElementById('saveEditCard')?.addEventListener('click', () => this.saveEditedCard());
    }
    /* --------------------------------------------
       CARD
    -------------------------------------------- */

    async createNewCard() {
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
        const data = await apiClient.post('/api/kanban/card/create', { title, detail, cardType });
        if (data.success) location.reload();
        else alert('Erreur lors de la création de la carte : ' + (data.error || 'Erreur inconnue'));
    }

    async deleteCard(cardId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) {
            return;
        }
        const data = await apiClient.post('/api/kanban/card/delete', { id: parseInt(cardId) });
        if (data.success) {
            const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
            card.remove();
        } else {
            alert('Erreur : ' + (data.error || 'Erreur inconnue'));
        }
    }

    async saveEditedCard() {
        const cardId = document.getElementById('editCardId').value;
        const title = document.getElementById('editCardTitle').value.trim();
        const detail = document.getElementById('editCardDetail').value.trim();

        if (!title) {
            alert('Le titre est obligatoire');
            return;
        }
        const data = await apiClient.post('/api/kanban/card/update', { id: parseInt(cardId), title, detail, typeId: parseInt(document.getElementById('editCardType').value) });
        if (data.success) {
            const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
            card.querySelector('.kanban-card-title').textContent = title;
            const detailElement = card.querySelector('.kanban-card-detail');
            if (detailElement) {
                detailElement.textContent = detail;
            } else if (detail) {
                const newDetailDiv = document.createElement('div');
                newDetailDiv.className = 'kanban-card-detail';
                newDetailDiv.textContent = detail;
                card.querySelector('.kanban-card-title').after(newDetailDiv);
            }
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCardModal'));
            modal.hide();
        } else alert('Erreur : ' + (data.error || 'Erreur inconnue'));
    }

    openEditModal(cardId) {
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

    /* --------------------------------------------
        DRAG And DROP
    -------------------------------------------- */
    initDragAndDrop() {
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('dragstart', e => this.handleDragStart(e, card));
            card.addEventListener('dragend', e => this.handleDragEnd(e, card));
        });
        document.querySelectorAll('.kanban-cards').forEach(column => {
            column.addEventListener('dragover', e => this.handleDragOver(e, column));
            column.addEventListener('drop', e => this.handleDrop(e, column));
            column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
        });
    }

    handleDragStart(e, card) {
        this.draggedCard = card;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    handleDragEnd(_, card) {
        card.classList.remove('dragging');
    }

    handleDragOver(e, column) {
        e.preventDefault();
        column.classList.add('drag-over');
    }

    handleDragLeave(e) {
        this.classList.remove('drag-over');
    }

    async handleDrop(e, column) {
        e.stopPropagation();
        column.classList.remove('drag-over');
        if (!this.draggedCard) return;

        const oldStatus = this.draggedCard.dataset.status;
        const newStatus = column.dataset.status;
        const cardId = this.draggedCard.dataset.id;

        if (oldStatus !== newStatus) {
            const changeType = this.statusTransitions[oldStatus][newStatus];
            if (changeType) {
                column.appendChild(this.draggedCard);
                this.draggedCard.dataset.status = newStatus;
                await this.moveCardToStatus(cardId, newStatus, changeType);
            }
        }
    }

    async moveCardToStatus(cardId, newStatus, changeType) {
        const data = await apiClient.post('/api/kanban/card/move', {
            id: parseInt(cardId),
            status: newStatus,
            changeType,
            remark: ''
        });
        if (!data.success) location.reload();
    }

    async updateKanbanBoard(cards) {
        document.querySelectorAll('.kanban-cards').forEach(column => {
            column.innerHTML = '';
        });

        cards.forEach(card => {
            if (!card || !card.CurrentStatus) return;

            const safeStatus = CSS.escape(card.CurrentStatus);
            const column = document.querySelector(`.kanban-cards[data-status="${safeStatus}"]`);

            if (!column) {
                console.warn("Colonne introuvable pour status:", card.CurrentStatus);
                return;
            }

            const cardElement = this.createCardElement(card);
            column.appendChild(cardElement);
        });

        this.initializeDragAndDrop();
    }

    createCardElement(card) {
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

        cardDiv.addEventListener('dragstart', this.handleDragStart);
        cardDiv.addEventListener('dragend', this.handleDragEnd);

        actionsDiv.querySelector('.edit-card').addEventListener('click', () => { this.openEditModal(card.Id); });
        actionsDiv.querySelector('.delete-card').addEventListener('click', () => { this.deleteCard(card.Id); });
        return cardDiv;
    }

    initializeDragAndDrop() {
        const cards = document.querySelectorAll('.kanban-card');
        cards.forEach(card => {
            card.addEventListener('dragstart', this.handleDragStart);
            card.addEventListener('dragend', this.handleDragEnd);
        });

        const columns = document.querySelectorAll('.kanban-cards');
        columns.forEach(column => {
            column.addEventListener('dragover', this.handleDragOver);
            column.addEventListener('drop', this.handleDrop);
            column.addEventListener('dragleave', this.handleDragLeave);
        });
    }
}
