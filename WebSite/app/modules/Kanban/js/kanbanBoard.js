import ApiClient from "../../Common/js/apiClient.js";
import DragDropManager from "../../Common/js/dragDropManager.js";
import CardManager from "./project/cardType//card/cardManager.js";

const apiClient = new ApiClient();
const cardManager = new CardManager();

export default class KanbanBoard {
    constructor(statusTransitions) {
        this.statusTransitions = statusTransitions;
        this.handleClick = this.handleClick.bind(this);
        this.dragDropManager = new DragDropManager({
            itemSelector: '.kanban-card',
            containerSelector: '.kanban-cards',
            draggedItemClass: 'dragging',
            dragOverClass: 'drag-over',
            debug: false,
            onDrop: (card, column) => this.handleCardDrop(card, column),
            canDrop: (card, column) => this.canDropCard(card, column)
        });
    }

    /* --------------------------------------------
        INIT
    -------------------------------------------- */
    init() {
        this.dragDropManager.init();
        this.initGlobalEvents();

        document.getElementById('saveNewCard')?.addEventListener('click', () => this.createNewCard());
        document.getElementById('saveEditCard')?.addEventListener('click', () => this.saveEditedCard());
        document.getElementById('saveEditCard')?.addEventListener('click', () => this.saveEditedCard());

        this.initTooltips();
    }

    initGlobalEvents() {
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
        const viewBtn = e.target.closest('.view-card');

        if (editBtn) this.openEditModal(editBtn.dataset.id);
        if (deleteBtn) this.deleteCard(deleteBtn.dataset.id);
        if (viewBtn) this.openViewModal(viewBtn.dataset.id);
    }

    update(cards) {
        document.querySelectorAll('.kanban-cards').forEach(column => {
            column.innerHTML = '';
        });

        const counts = {};
        cards.forEach(card => {
            const status = card.CurrentStatus;
            counts[status] = (counts[status] || 0) + 1;

            const column = document.querySelector(
                `.kanban-cards[data-status="${status}"]`
            );
            if (column) {
                column.appendChild(this.createCardElement(card));
            }
        });

        document.querySelectorAll('.kanban-column').forEach(column => {
            const status = column.dataset.status;
            const title = column.querySelector('.kanban-column-title');

            if (title) {
                const baseLabel = status; // ou mapping si besoin
                const count = counts[status] || 0;
                title.textContent = `${baseLabel} (${count})`;
            }
        });

        this.dragDropManager.init();
    }

    createCardElement(card) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'kanban-card';
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
            <button class="btn btn-sm btn-success view-card" data-id="${card.Id}" title="Voir l'historique">
                <i class="bi bi-eye"></i>
            </button>
            <button class="btn btn-sm btn-warning edit-card" data-id="${card.Id}" title="Éditer">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-danger delete-card" data-id="${card.Id}" title="Supprimer">
                <i class="bi bi-trash"></i>
            </button>
        `;
        cardDiv.appendChild(actionsDiv);

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

        const response = await cardManager.create(title, detail, cardType);
        if (!response.success) return alert(data.error || 'Create card failed');
        location.reload();
    }

    async deleteCard(cardId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) return;

        const response = await cardManager.delete(cardId);
        if (!response.success) return alert(data.error || 'Delete card failed');
        document.querySelector(`.kanban-card[data-id="${cardId}"]`)?.remove();
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

    async openViewModal(cardId) {
        const result = await cardManager.history(cardId);
        if (result.success) {
            const historyList = document.getElementById('cardHistoryList');
            historyList.innerHTML = '';

            result.history.forEach((entry, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item';

                const remarkId = `remark-${index}`;
                li.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fs-5 me-2">${entry.Status}</span>
                            <small class="text-muted">${new Date(entry.LastUpdate).toLocaleString('fr-FR')}</small>
                        </div>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="${remarkId}" value="${entry.Remark || ''}" placeholder="Ajouter une remarque...">
                        <button class="btn btn-primary save-remark"
                            data-status-id="${entry.Id}"
                            data-input-id="${remarkId}">
                            <i class="bi bi-save"></i>
                        </button>
                    </div>
                `;
                historyList.appendChild(li);
                li.querySelector('.save-remark').addEventListener('click', (e) => {
                    const btn = e.currentTarget;
                    this.updateRemark(btn.dataset.statusId, btn.dataset.inputId);
                });
            });
            new bootstrap.Modal(document.getElementById('viewCardModal')).show();
        }
    }

    async updateRemark(statusId, remarkInputId) {
        const input = document.getElementById(remarkInputId);
        const remark = input.value;

        const response = await cardManager.updateStatus(statusId, remark);
        if (!response.success) return alert(data.error || 'Update card status failed');

        const modalEl = document.getElementById('viewCardModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        modalInstance?.hide();
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

            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
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
        DRAG & DROP KANBAN
    -------------------------------------------- */
    canDropCard(card, column) {
        if (!card || !column) return false;

        const oldStatus = card.dataset.status;
        const newStatus = column.dataset.status;

        return oldStatus !== newStatus;
    }

    async handleCardDrop(card, column) {
        const oldStatus = card.dataset.status;
        const newStatus = column.dataset.status;
        const cardId = card.dataset.id;

        if (oldStatus === newStatus) return false;

        column.appendChild(card);
        card.dataset.status = newStatus;
        const what = this.statusTransitions?.[oldStatus]?.[newStatus] || '???';
        const remark = `${oldStatus} → ${newStatus}`;
        const response = await cardManager.move(cardId, what, remark);
        if (response.success) return true;
        return false;
    }

    destroy() {
        this.dragDropManager.destroy();
        document.removeEventListener('click', this.handleClick);
    }
}