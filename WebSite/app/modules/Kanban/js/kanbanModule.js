import KanbanBoard from "./kanbanBoard.js";
import ProjectManager from "./projectManager.js";
import CardTypeManager from "./cardTypeManager.js";

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
        const project = await this.projectManager.load(projectId);
        if (!project.success) return;

        document.getElementById('editProjectId').value = project.data.id;
        document.getElementById('editProjectTitle').value = project.data.title;
        document.getElementById('editProjectDescription').value = project.data.detail;

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
    }

    async createNewCardType() {
        const projectId = document.getElementById('kanbanProjectSelect').value;
        const label = document.getElementById('cardTypeLabel').value.trim();
        const detail = document.getElementById('cardTypeDetail').value.trim();

        if (!projectId) return alert("Sélectionnez un projet.");

        const result = await this.cardTypeManager.create(projectId, label, detail);
        if (result.success) location.reload();
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
