import KanbanBoard from "./kanbanBoard.js";
import ProjectManager from "./project/projectManager.js";
import CardTypeManager from "./project/cardType/cardTypeManager.js";

export default class KanbanModule {
    constructor(statusTransitions) {
        this.statusTransitions = statusTransitions;

        this.kanbanBoard = new KanbanBoard(statusTransitions);
        this.projectManager = new ProjectManager();
        this.cardTypeManager = new CardTypeManager();

        this.cacheDom();
    }

    /* --------------------------------------------
       INIT
    -------------------------------------------- */
    init() {
        this.kanbanBoard.init();
        this.attachEvents();
        this.handleProjectSelection();
    }

    /* --------------------------------------------
       DOM CACHE
    -------------------------------------------- */
    cacheDom() {
        this.dom = {
            projectSelect: document.getElementById("kanbanProjectSelect"),
            addProjectBtn: document.getElementById("addProjectBtn"),
            addCardBtn: document.getElementById("addCardBtn"),
            editProjectBtn: document.getElementById("editProjectBtn"),
            deleteProjectBtn: document.getElementById("deleteProjectBtn"),
            kanbanBoard: document.getElementById("kanbanBoard"),
            statsContainer: document.getElementById("statsContainer"),

            cardTypesList: document.getElementById("cardTypesList"),
            newCardTypeForm: document.getElementById("newCardTypeForm"),
            newCardTypeLabel: document.getElementById("newCardTypeLabel"),
            newCardTypeDetail: document.getElementById("newCardTypeDetail"),

            editProjectModal: document.getElementById("editProjectModal"),
            editProjectId: document.getElementById("editProjectId"),
            editProjectTitle: document.getElementById("editProjectTitle"),
            editProjectDescription: document.getElementById("editProjectDescription"),

            addCardModal: document.getElementById("addCardModal"),
            editCardModal: document.getElementById("editCardModal"),
        };
    }

    /* --------------------------------------------
       ERROR HANDLING
    -------------------------------------------- */
    handleError(message, details = null) {
        console.error(message, details);
        alert(message);
    }

    /* --------------------------------------------
       PROJECT SELECTION
    -------------------------------------------- */
    handleProjectSelection() {
        this.dom.projectSelect.addEventListener("change", async () => {
            const projectId = this.dom.projectSelect.value;
            if (!projectId) {
                this.hideProjectUI();
                return;
            }

            this.showProjectUI(projectId);

            // recharge les types de cartes pour le projet sélectionné
            const response = await this.cardTypeManager.load(projectId);
            if (response.success) {
                this.populateCardTypeSelects(response.cardTypes ?? []);
            }
        });
    }

    showProjectUI(projectId) {
        this.toggleProjectUI(true);
        this.loadProjectCards(projectId);
    }

    hideProjectUI() {
        this.toggleProjectUI(false);
    }

    toggleProjectUI(visible) {
        const method = visible ? "remove" : "add";
        this.dom.addProjectBtn.classList[visible ? "add" : "remove"]("d-none");
        this.dom.addCardBtn.classList[method]("d-none");
        this.dom.editProjectBtn.classList[method]("d-none");
        this.dom.kanbanBoard.classList[method]("d-none");
        this.dom.statsContainer.classList[method]("d-none");
    }

    /* --------------------------------------------
       KANBAN
    -------------------------------------------- */
    async loadProjectCards(projectId) {
        const result = await this.projectManager.getCards(projectId);
        if (!result.success) {
            this.handleError("Impossible de charger les cartes", result);
            return;
        }
        this.kanbanBoard.update(result.cards ?? []);
    }

    /* --------------------------------------------
       PROJECT CRUD
    -------------------------------------------- */
    async createProject() {
        const title = document.getElementById('projectTitle').value.trim();
        const detail = document.getElementById('projectDescription').value.trim();

        const result = await this.projectManager.create(title, detail);
        if (!result.success) {
            this.handleError("Création du projet échouée", result);
            return;
        }
        location.reload();
    }

    async deleteProject() {
        const projectId = this.dom.projectSelect.value;
        if (!projectId) return alert("Sélectionnez un projet");
        if (!confirm("Supprimer ce projet ?")) return;

        const result = await this.projectManager.delete(projectId);
        if (!result.success) {
            this.handleError("Suppression du projet impossible", result);
            return;
        }
        location.reload();
    }

    async loadProjectForEdit(projectId) {
        const response = await this.projectManager.load(projectId);
        if (!response.success || !response.project) {
            this.handleError("Projet invalide", response);
            return;
        }
        const { Id, Title, Detail } = response.project;
        this.dom.editProjectId.value = Id;
        this.dom.editProjectTitle.value = Title;
        this.dom.editProjectDescription.value = Detail;

        await this.reloadCardTypes(Id);
        this.dom.editProjectModal.classList.remove("d-none");
    }

    async saveEditedProject() {
        const { editProjectId, editProjectTitle, editProjectDescription } = this.dom;
        const result = await this.projectManager.update(
            editProjectId.value,
            editProjectTitle.value.trim(),
            editProjectDescription.value.trim()
        );

        if (!result.success) {
            this.handleError("Sauvegarde du projet impossible", result);
            return;
        }
        bootstrap.Modal.getOrCreateInstance(this.dom.editProjectModal).hide();
    }

    /* --------------------------------------------
       CARD TYPES
    -------------------------------------------- */
        showNewCardTypeForm() {
        this.dom.newCardTypeForm.classList.remove("d-none");
    }

    hideNewCardTypeForm() {
        this.dom.newCardTypeForm.classList.add("d-none");
        this.dom.newCardTypeLabel.value = "";
        this.dom.newCardTypeDetail.value = "";
    }

    async createNewCardType() {
        const projectId = this.dom.projectSelect.value;
        if (!projectId) return alert("Sélectionnez un projet");

        const label = this.dom.newCardTypeLabel.value.trim();
        const detail = this.dom.newCardTypeDetail.value.trim();

        const result = await this.cardTypeManager.create(projectId, label, detail);
        if (!result.success) {
            this.handleError("Création du type de carte impossible", result);
            return;
        }
        await this.reloadCardTypes(projectId);
        this.hideNewCardTypeForm();
    }

    async deleteCardType(cardTypeId) {
        if (!confirm("Supprimer ce type de carte ?")) return;

        const response = await this.cardTypeManager.delete(cardTypeId);
        if (!response.success) {
            this.handleError("Suppression du type de carte impossible", response);
            return;
        }
        await this.reloadCardTypes(this.dom.editProjectId.value);
    }

    async reloadCardTypes(projectId) {
        const response = await this.cardTypeManager.load(projectId);
        if (!response.success) {
            this.handleError("Chargement des types de carte impossible", response);
            return;
        }
        this.displayCardTypes(response.cardTypes ?? []);
        this.populateCardTypeSelects(response.cardTypes ?? []);
    }

    displayCardTypes(cardTypes) {
        const container = this.dom.cardTypesList;
        container.innerHTML = "";

        if (cardTypes.length === 0) {
            const p = document.createElement("p");
            p.className = "text-muted";
            p.textContent = "Aucun type de carte défini";
            container.appendChild(p);
            return;
        }

        cardTypes.forEach(type => {
            const wrapper = document.createElement("div");
            wrapper.className = "card-type-item d-flex justify-content-between align-items-start";

            const info = document.createElement("div");
            info.className = "flex-grow-1";

            const label = document.createElement("span");
            label.className = "fw-bold";
            label.textContent = type.Label;
            info.appendChild(label);

            if (type.Detail) {
                const detail = document.createElement("span");
                detail.className = "text-muted small";
                detail.textContent = ` - ${type.Detail}`;
                info.appendChild(detail);
            }

            const btn = document.createElement("button");
            btn.className = "btn btn-sm btn-danger";
            btn.innerHTML = '<i class="bi bi-trash"></i>';
            btn.addEventListener("click", () => this.deleteCardType(type.Id));

            wrapper.appendChild(info);
            wrapper.appendChild(btn);
            container.appendChild(wrapper);
        });
    }

    populateCardTypeSelects(cardTypes) {
        const selects = [
            document.getElementById("cardType"),
            document.getElementById("editCardType")
        ];

        selects.forEach(select => {
            if (!select) return;
            select.innerHTML = '<option value="">Choisir un type</option>';
            cardTypes.forEach(type => {
                const option = document.createElement("option");
                option.value = type.Id;
                option.textContent = type.Label;
                select.appendChild(option);
            });
        });
    }

    /* --------------------------------------------
       EVENTS
    -------------------------------------------- */
    attachEvents() {
        document.getElementById('saveNewProject')?.addEventListener('click', () => this.createProject());
        this.dom.editProjectBtn?.addEventListener('click', () => {
            const projectId = this.dom.projectSelect.value;
            if (projectId) this.loadProjectForEdit(projectId);
        });
        document.getElementById('saveEditProject')?.addEventListener('click', () => this.saveEditedProject());
        this.dom.deleteProjectBtn?.addEventListener('click', () => this.deleteProject());
        document.getElementById('addCardTypeBtn')?.addEventListener('click', () => this.showNewCardTypeForm());
        document.getElementById('saveNewCardType')?.addEventListener('click', () => this.createNewCardType());
        document.getElementById('cancelNewCardType')?.addEventListener('click', () => this.hideNewCardTypeForm());

        // Charger les types de cartes à l'ouverture des modales
        this.dom.addCardModal?.addEventListener('show.bs.modal', async () => {
            const projectId = this.dom.projectSelect.value;
            if (!projectId) return;
            const response = await this.cardTypeManager.load(projectId);
            if (response.success) this.populateCardTypeSelects(response.cardTypes ?? []);
            else this.handleError("Chargement des types de carte impossible", response);
        });

        this.dom.editCardModal?.addEventListener('show.bs.modal', async () => {
            const projectId = this.dom.projectSelect.value;
            if (!projectId) return;
            const response = await this.cardTypeManager.load(projectId);
            if (response.success) this.populateCardTypeSelects(response.cardTypes ?? []);
            else this.handleError("Chargement des types de carte impossible", response);
        });
    }
}
