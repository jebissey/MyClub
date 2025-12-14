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

        document.getElementById('saveNewCard')?.addEventListener('click', () => this.createNewCard());
        document.getElementById('saveEditCard')?.addEventListener('click', () => this.saveEditedCard());

        this.initTooltips();
    }

    initGlobalEvents() {
        // Event delegation pour boutons éditer / supprimer
        document.addEventListener('click', this.handleClick);
    }

    initTooltips(root = document) {
        root.querySelectorAll('[data-bs-title]').forEach(el => {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });
    }

    handleClick(e) {
        const editBtn = e.target.closest('.edit-card');
        const deleteBtn = e.target.closest('.delete-card');

        if (editBtn) this.openEditModal(editBtn.dataset.id);
        if (deleteBtn) this.deleteCard(deleteBtn.dataset.id);
    }

    update(cards) {
        document.querySelectorAll('.kanban-cards').forEach(column => column.innerHTML = '');
        cards.forEach(card => {
            const column = document.querySelector(`.kanban-cards[data-status="${card.CurrentStatus}"]`);
            if (column) column.appendChild(this.createCardElement(card));
        });
        this.initDragAndDrop();
    }

    createCardElement(card) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'kanban-card';
        cardDiv.draggable = true;
        cardDiv.dataset.id = card.Id;
        cardDiv.dataset.status = card.CurrentStatus;

        const titleDiv = document.createElement('div');
        titleDiv.className = 'kanban-card-title fw-bold';

        const labelSpan = document.createElement('span');
        labelSpan.className = 'kanban-card-type';
        labelSpan.textContent = card.Label;

        const titleSpan = document.createElement('span');
        titleSpan.className = 'kanban-title-text';
        titleSpan.textContent = card.Title;

        titleDiv.appendChild(labelSpan);
        titleDiv.appendChild(titleSpan);
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

        actionsDiv.querySelector('.edit-card').addEventListener('click', () => this.openEditModal(card.Id));
        actionsDiv.querySelector('.delete-card').addEventListener('click', () => this.deleteCard(card.Id));

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
            const data = await apiClient.post('/api/kanban/card/create', { title, detail, cardType });
            if (!data.success) return alert(data.error || 'Erreur lors de la création');
            location.reload();
        } catch (e) {
            console.error(e);
            alert('Erreur réseau');
        }
    }

    async deleteCard(cardId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) return;

        try {
            const data = await apiClient.post('/api/kanban/card/delete', { id: Number(cardId) });
            if (!data.success) return alert(data.error || 'Erreur inconnue');

            document.querySelector(`.kanban-card[data-id="${cardId}"]`)?.remove();
        } catch (e) {
            console.error(e);
            alert('Erreur réseau');
        }
    }

    openEditModal(cardId) {
        const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
        if (!card) return;

        document.getElementById('editCardId').value = cardId;
        document.getElementById('editCardTitle').value = card.querySelector('.kanban-title-text')?.textContent || '';
        document.getElementById('editCardDetail').value = card.querySelector('.kanban-card-detail')?.textContent || '';
        document.getElementById('cardTypeLabel').value = card.querySelector('.kanban-card-type')?.textContent || '';

        new bootstrap.Modal(document.getElementById('editCardModal')).show();
    }

    async saveEditedCard() {
        const cardId = document.getElementById('editCardId')?.value;
        const title = document.getElementById('editCardTitle')?.value.trim();
        const detail = document.getElementById('editCardDetail')?.value.trim();

        if (!title) return alert('Le titre est obligatoire');

        try {
            const data = await apiClient.post('/api/kanban/card/update', { id: Number(cardId), title, detail });
            if (!data.success) return alert(data.error || 'Erreur inconnue');

            this.updateCardInDOM(cardId, title, detail);

            const modalEl = document.getElementById('editCardModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance?.hide();

            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(b => b.remove());

            document.body.classList.remove('modal-open');

        } catch (e) {
            alert('Erreur réseau' + e);
        }
    }


    updateCardInDOM(cardId, title, detail) {
        const card = document.querySelector(`.kanban-card[data-id="${cardId}"]`);
        if (!card) return;

        card.querySelector('.kanban-title-text').textContent = title;

        let detailDiv = card.querySelector('.kanban-card-detail');
        if (detail) {
            if (!detailDiv) {
                detailDiv = document.createElement('div');
                detailDiv.className = 'kanban-card-detail';
                card.querySelector('.kanban-title-text').after(detailDiv);
            }
            detailDiv.textContent = detail;
        } else detailDiv?.remove();
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
