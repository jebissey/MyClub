import KanbanBoard from "./kanbanBoard.js";
import ProjectManager from "./project/projectManager.js";
import CardTypeManager from "./project/cardType/cardTypeManager.js";

export default class KanbanModule {
    constructor(statusTransitions) {
        this.statusTransitions = statusTransitions;

        this.kanbanBoard = new KanbanBoard(statusTransitions);
        this.projectManager = new ProjectManager();
        this.cardTypeManager = new CardTypeManager();
        this.cardTypes = [];
        this.editingCardTypeId = null;

        this.cacheDom();
    }

    /* --------------------------------------------
       INIT
    -------------------------------------------- */
    async init() {
        this.kanbanBoard.init();
        this.attachEvents();

        const projectId = this.dom.projectSelect.value;
        if (projectId) {
            const response = await this.cardTypeManager.load(projectId);
            if (response.success) {
                this.cardTypes = response.data.cardTypes ?? [];
                this.populateCardTypeSelects(this.cardTypes);
            }
            this.showProjectUI(projectId);
        }
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
    async showProjectUI(projectId) {
        this.toggleProjectUI(true);
        await this.loadProjectCards(projectId);
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
    }

    /* --------------------------------------------
       KANBAN
    -------------------------------------------- */
    async loadProjectCards(projectId) {
        const urlParams = new URLSearchParams(window.location.search);
        const filterCt = urlParams.get('ct') || '';
        const filterTitle = urlParams.get('title') || '';
        const filterDetail = urlParams.get('detail') || '';

        const result = await this.projectManager.getCards(projectId, filterCt, filterTitle, filterDetail);
        if (!result.success) {
            this.handleError("Impossible de charger les cartes", result);
            return;
        }
        this.kanbanBoard.update(result.data.cards ?? [], this.cardTypes);
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
        if (!response.success || !response.data.project) {
            this.handleError("Projet invalide", response);
            return;
        }
        const { Id, Title, Detail } = response.data.project;
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

        // Remettre la couleur par défaut (bg-warning-subtle)
        const defaultColor = this.dom.newCardTypeForm.querySelector('input[name="newCardTypeColor"][value="bg-warning-subtle"]');
        if (defaultColor) defaultColor.checked = true;
    }

    async createNewCardType() {

        const projectId = this.dom.projectSelect.value;
        if (!projectId) return alert("Sélectionnez un projet");

        const label = this.dom.newCardTypeLabel.value.trim();
        const detail = this.dom.newCardTypeDetail.value.trim();

        const color = this.dom.newCardTypeForm
            .querySelector('input[name="newCardTypeColor"]:checked')
            ?.value ?? 'bg-warning-subtle';

        let result;

        if (this.editingCardTypeId) {
            result = await this.cardTypeManager.update(
                this.editingCardTypeId,
                label,
                detail,
                color
            );
        } else {
            result = await this.cardTypeManager.create(
                projectId,
                label,
                detail,
                color
            );
        }

        if (!result.success) {
            this.handleError("Opération impossible", result);
            return;
        }

        this.editingCardTypeId = null;
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

    async editCardType(cardTypeId) {

        const projectId = this.dom.projectSelect.value;
        if (!projectId) return alert("Sélectionnez un projet");

        const cardType = this.cardTypes.find(t => t.Id === cardTypeId);
        if (!cardType) {
            this.handleError("Type de carte introuvable");
            return;
        }

        this.editingCardTypeId = cardTypeId;

        this.dom.newCardTypeLabel.value = cardType.Label ?? "";
        this.dom.newCardTypeDetail.value = cardType.Detail ?? "";

        const color = cardType.Color ?? "bg-warning-subtle";
        const radio = this.dom.newCardTypeForm
            .querySelector(`input[name="newCardTypeColor"][value="${color}"]`);
        if (radio) radio.checked = true;

        this.showNewCardTypeForm();
    }

    async reloadCardTypes(projectId) {
        const response = await this.cardTypeManager.load(projectId);
        if (!response.success) {
            this.handleError("Chargement des types de carte impossible", response);
            return;
        }
        this.cardTypes = response.data.cardTypes ?? [];
        this.displayCardTypes(this.cardTypes);
        this.populateCardTypeSelects(this.cardTypes);
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
            wrapper.className = "card-type-item d-flex justify-content-between align-items-center mb-2";

            const info = document.createElement("div");
            info.className = "d-flex align-items-center gap-2 flex-grow-1";

            const swatch = document.createElement("span");
            swatch.className = `border rounded ${type.Color || 'bg-warning-subtle'}`;
            swatch.style.cssText = "display:inline-block; width:1.2rem; height:1.2rem; flex-shrink:0;";
            info.appendChild(swatch);

            const label = document.createElement("span");
            label.className = "fw-bold";
            label.textContent = type.Label;
            info.appendChild(label);

            if (type.Detail) {
                const detail = document.createElement("span");
                detail.className = "text-muted small ms-1";
                detail.textContent = type.Detail;
                info.appendChild(detail);
            }

            const actions = document.createElement("div");
            actions.className = "d-flex gap-2";

            const editBtn = document.createElement("button");
            editBtn.className = "btn btn-sm btn-warning";
            editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
            editBtn.addEventListener("click", () => this.editCardType(type.Id));

            const deleteBtn = document.createElement("button");
            deleteBtn.className = "btn btn-sm btn-danger";
            deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
            deleteBtn.addEventListener("click", () => this.deleteCardType(type.Id));

            actions.appendChild(editBtn);
            actions.appendChild(deleteBtn);

            wrapper.appendChild(info);
            wrapper.appendChild(actions);
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
            if (response.success) this.populateCardTypeSelects(response.data.cardTypes ?? []);
            else this.handleError("Chargement des types de carte impossible", response);
        });

        this.dom.editCardModal?.addEventListener('show.bs.modal', async () => {
            const projectId = this.dom.projectSelect.value;
            if (!projectId) return;
            const response = await this.cardTypeManager.load(projectId);
            if (response.success) this.populateCardTypeSelects(response.data.cardTypes ?? []);
            else this.handleError("Chargement des types de carte impossible", response);
        });
    }
}