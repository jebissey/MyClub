import ApiClient from "../../Common/js/apiClient.js";

const apiClient = new ApiClient();

export default class KanbanBoard {
    constructor(statusTransitions) {
        this.statusTransitions = statusTransitions;
        this.draggedCard = null;

        this.handleDragStart = this.handleDragStart.bind(this);
        this.handleDragEnd = this.handleDragEnd.bind(this);
        this.handleDragOver = this.handleDragOver.bind(this);
        this.handleDrop = this.handleDrop.bind(this);
        this.handleDragLeave = this.handleDragLeave.bind(this);
        this.handleClick = this.handleClick.bind(this);
    }

    /* --------------------------------------------
        INIT
    -------------------------------------------- */
    init() {
        this.initDragAndDrop();
        this.initGlobalEvents();

        document.getElementById('saveNewCard')
            ?.addEventListener('click', () => this.createNewCard());
        document.getElementById('saveEditCard')
            ?.addEventListener('click', () => this.saveEditedCard());

        this.initModalEvents();
    }

    initGlobalEvents() {
        // Event delegation for edit / delete buttons
        document.addEventListener('click', this.handleClick);
    }

    initModalEvents() {
        const addModal = document.getElementById('addCardModal');
        const editModal = document.getElementById('editCardModal');

        addModal?.addEventListener('show.bs.modal', () => {
            this.reloadCardTypes(this.projectId);
        });

        editModal?.addEventListener('show.bs.modal', () => {
            this.reloadCardTypes(this.projectId);
        });
    }

    handleClick(e) {
        const editBtn = e.target.closest('.edit-card');
        const deleteBtn = e.target.closest('.delete-card');

        if (editBtn) {
            this.openEditModal(editBtn.dataset.id);
        }
        if (deleteBtn) {
            this.deleteCard(deleteBtn.dataset.id);
        }
    }

    update(cards) {
        document.querySelectorAll('.kanban-cards').forEach(column => {
            column.innerHTML = '';
        });
        cards.forEach(card => {
            const column = document.querySelector(`.kanban-cards[data-status="${card.CurrentStatus}"]`);
            if (column) {
                const cardElement = this.createCardElement(card);
                column.appendChild(cardElement);
            }
        });
        this.initDragAndDrop();
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

        actionsDiv.querySelector('.edit-card').addEventListener('click', function () {
            this.openEditModal(card.Id);
        });

        actionsDiv.querySelector('.delete-card').addEventListener('click', function () {
            this.deleteCard(card.Id);
        });

        return cardDiv;
    }

    /* --------------------------------------------
        CARD CRUD
    -------------------------------------------- */
    async createNewCard() {
        const title = document.getElementById('cardTitle')?.value.trim();
        const detail = document.getElementById('cardDetail')?.value.trim();
        const cardType = document.getElementById('cardType')?.value;

        if (!title) return alert('Le titre est obligatoire');
        if (!cardType) return alert('Veuillez sélectionner un type de carte');

        try {
            const data = await apiClient.post('/api/kanban/card/create', {
                title,
                detail,
                cardType
            });

            if (!data.success) {
                alert(data.error || 'Erreur lors de la création');
                return;
            }
            location.reload();
        } catch (e) {
            console.error(e);
            alert('Erreur réseau');
        }
    }

    async deleteCard(cardId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) return;

        try {
            const data = await apiClient.post('/api/kanban/card/delete', {
                id: Number(cardId)
            });

            if (!data.success) {
                alert(data.error || 'Erreur inconnue');
                return;
            }

            document
                .querySelector(`.kanban-card[data-id="${cardId}"]`)
                ?.remove();
        } catch (e) {
            console.error(e);
            alert('Erreur réseau');
        }
    }

    async saveEditedCard() {
        const cardId = document.getElementById('editCardId')?.value;
        const title = document.getElementById('editCardTitle')?.value.trim();
        const detail = document.getElementById('editCardDetail')?.value.trim();
        const typeId = Number(document.getElementById('editCardType')?.value);

        if (!title) return alert('Le titre est obligatoire');

        try {
            const data = await apiClient.post('/api/kanban/card/update', {
                id: Number(cardId),
                title,
                detail,
                typeId
            });

            if (!data.success) {
                alert(data.error || 'Erreur inconnue');
                return;
            }

            this.updateCardInDOM(cardId, title, detail);
            bootstrap.Modal.getInstance(
                document.getElementById('editCardModal')
            )?.hide();
        } catch (e) {
            console.error(e);
            alert('Erreur réseau');
        }
    }

    updateCardInDOM(cardId, title, detail) {
        const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
        if (!card) return;

        card.querySelector('.kanban-card-title').textContent = title;

        let detailDiv = card.querySelector('.kanban-card-detail');
        if (detail) {
            if (!detailDiv) {
                detailDiv = document.createElement('div');
                detailDiv.className = 'kanban-card-detail';
                card.querySelector('.kanban-card-title').after(detailDiv);
            }
            detailDiv.textContent = detail;
        } else if (detailDiv) {
            detailDiv.remove();
        }
    }

    openEditModal(cardId) {
        const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
        if (!card) return;

        document.getElementById('editCardId').value = cardId;
        document.getElementById('editCardTitle').value =
            card.querySelector('.kanban-card-title').textContent;
        document.getElementById('editCardDetail').value =
            card.querySelector('.kanban-card-detail')?.textContent || '';
        document.getElementById('editCardType').value = card.dataset.typeId;

        new bootstrap.Modal(
            document.getElementById('editCardModal')
        ).show();
    }

    displayCardTypes(cardTypes) {
        const selects = [
            document.getElementById('cardType'),
            document.getElementById('editCardType')
        ];

        selects.forEach(select => {
            if (!select) return;

            // Reset
            select.innerHTML = '<option value="">Choisir un type</option>';

            cardTypes.forEach(type => {
                const option = document.createElement('option');
                option.value = type.Id;
                option.textContent = type.Label;
                select.appendChild(option);
            });
        });
    }

    /* --------------------------------------------
        DRAG & DROP
    -------------------------------------------- */
    initDragAndDrop() {
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('dragstart', this.handleDragStart);
            card.addEventListener('dragend', this.handleDragEnd);
        });

        document.querySelectorAll('.kanban-cards').forEach(column => {
            column.addEventListener('dragover', this.handleDragOver);
            column.addEventListener('drop', this.handleDrop);
            column.addEventListener('dragleave', this.handleDragLeave);
        });
    }

    handleDragStart(e) {
        this.draggedCard = e.currentTarget;
        this.draggedCard.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    handleDragEnd(e) {
        e.currentTarget.classList.remove('dragging');
        this.draggedCard = null;
    }

    handleDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    }

    handleDragLeave(e) {
        e.currentTarget.classList.remove('drag-over');
    }

    async handleDrop(e) {
        e.preventDefault();
        const column = e.currentTarget;
        column.classList.remove('drag-over');

        if (!this.draggedCard) return;

        const oldStatus = this.draggedCard.dataset.status;
        const newStatus = column.dataset.status;
        const cardId = this.draggedCard.dataset.id;

        if (oldStatus === newStatus) return;

        const changeType = this.statusTransitions?.[oldStatus]?.[newStatus];
        if (!changeType) return;

        column.appendChild(this.draggedCard);
        this.draggedCard.dataset.status = newStatus;

        try {
            const data = await apiClient.post('/api/kanban/card/move', {
                id: Number(cardId),
                status: newStatus,
                changeType,
                remark: ''
            });

            if (!data.success) location.reload();
        } catch (e) {
            console.error(e);
            location.reload();
        }
    }
}
