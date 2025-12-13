import KanbanBoard from "./kanbanBoard.js";
import ProjectManager from "./project/projectManager.js";
import CardTypeManager from "./project/cardType/cardTypeManager.js";

export default class KanbanModule {
    constructor(statusTransitions) {
        this.statusTransitions = statusTransitions;
        this.kanban = new KanbanBoard(statusTransitions);
        this.projectManager = new ProjectManager();
        this.cardTypeManager = new CardTypeManager();
    }

    init() {
        this.kanban.init();
        this.attachEvents();
        this.handleProjectSelection();
    }

    /* -------------------------
    Project Selection
    ------------------------- */
    handleProjectSelection() {
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
                addProjectBtn.classList.add("d-none");
                addCardBtn.classList.remove("d-none");
                editProjectBtn.classList.remove("d-none");
                kanbanBoard.classList.remove("d-none");
                statsContainer.classList.remove("d-none");

                this.loadProjectCards(select.value);
            }
        });
    }

    /* -------------------------
       Load Project Cards
    ------------------------- */
    async loadProjectCards(projectId) {
        const result = await this.projectManager.getCards(projectId);
        if (!result.success) return;

        const cards = result.cards || [];
        this.kanban.updateKanbanBoard(cards);
    }

    /* --------------------------------------------
       PROJECT METHODS
    -------------------------------------------- */
    async createProject() {
        const title = document.getElementById('projectTitle').value.trim();
        const detail = document.getElementById('projectDescription').value.trim();

        const result = await this.projectManager.create(title, detail);
        if (result.success) location.reload();
    }

    async loadProjectForEdit(projectId) {
        const response = await this.projectManager.load(projectId);
        if (!response.success || !response.project) {
            console.error('Projet invalide', response);
            location.reload();
            return;
        }
        document.getElementById('editProjectId').value = response.project.Id;
        document.getElementById('editProjectTitle').value = response.project.Title;
        document.getElementById('editProjectDescription').value = response.project.Detail;

        const response2 = await this.cardTypeManager.load(projectId);
        if (!response2.success || !response2.cardTypes) {
            console.error('Card type invalide', response2);
            location.reload();
            return;
        }
        this.displayCardTypes(response2.cardTypes);

        document.getElementById('editProjectModal').classList.remove('d-none');
    }

    async saveEditedProject() {
        const id = document.getElementById('editProjectId').value;
        const title = document.getElementById('editProjectTitle').value.trim();
        const detail = document.getElementById('editProjectDescription').value.trim();

        const result = await this.projectManager.update(id, title, detail);
        if (result.success) location.reload();
    }

    async deleteProject() {
        const id = document.getElementById('kanbanProjectSelect').value;
        if (!id) return alert("Sélectionnez un projet.");
        if (!confirm("Supprimer ce projet ?")) return;

        const result = await this.projectManager.delete(id);
        if (result.success) location.reload();
    }

    /* --------------------------------------------
       CARD TYPES METHODS
    -------------------------------------------- */
    showNewCardTypeForm() {
        document.getElementById('newCardTypeForm').classList.remove('d-none');
    }

    hideNewCardTypeForm() {
        document.getElementById('newCardTypeForm').classList.add('d-none');
        document.getElementById('newCardTypeLabel').value = '';
        document.getElementById('newCardTypeDetail').value = '';
    }

    async createNewCardType() {
        const projectId = document.getElementById('kanbanProjectSelect').value;
        const label = document.getElementById('newCardTypeLabel').value.trim();
        const detail = document.getElementById('newCardTypeDetail').value.trim();

        if (!projectId) return alert("Sélectionnez un projet.");

        const result = await this.cardTypeManager.create(projectId, label, detail);
        if (result.success) {
            const response2 = await this.cardTypeManager.load(projectId);
            if (!response2.success) {
                console.error('Card type invalide', response2);
                location.reload();
                return;
            }
            this.displayCardTypes(response2.cardTypes);
            this.hideNewCardTypeForm();
        } else location.reload();
    }

    async deleteCardType(cardTypeId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce type de carte ?')) {
            return;
        }
        const response = await this.cardTypeManager.delete(cardTypeId);
        if (!response.success || !response.project) {
            console.error('Card type Id invalide', response);
            location.reload();
            return;
        }
        const projectId = document.getElementById('editProjectId').value;
        const response2 = await this.cardTypeManager.load(projectId);
        if (!response2.success) {
            console.error('Card type invalide', response2);
            location.reload();
            return;
        }
        this.displayCardTypes(response2.cardTypes);

    }

    displayCardTypes(cardTypes) {
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
            typeElement.querySelector('.delete-card-type').addEventListener('click', () => {
                this.deleteCardType(type.Id);
            });
            container.appendChild(typeElement);
        });
    }

    /* --------------------------------------------
       EVENT ATTACHMENT
    -------------------------------------------- */
    attachEvents() {
        document.getElementById('saveNewProject')?.addEventListener('click', () => this.createProject());

        document.getElementById('editProjectBtn')?.addEventListener('click', () => {
            const projectId = document.getElementById('kanbanProjectSelect').value;
            if (projectId) this.loadProjectForEdit(projectId);
        });

        document.getElementById('saveEditProject')?.addEventListener('click', () => this.saveEditedProject());
        document.getElementById('deleteProjectBtn')?.addEventListener('click', () => this.deleteProject());

        document.getElementById('addCardTypeBtn')?.addEventListener('click', () => this.showNewCardTypeForm());
        document.getElementById('saveNewCardType')?.addEventListener('click', () => this.createNewCardType());
        document.getElementById('cancelNewCardType')?.addEventListener('click', () => this.hideNewCardTypeForm());
    }
}
