// navbarManager.js

export default class NavbarManager {
    constructor() {
        this.editModalEl = document.getElementById('editModal');
        this.modal = this.editModalEl ? new bootstrap.Modal(this.editModalEl) : null;

        this.routeSelect = document.getElementById('itemRoute');
        this.idParamContainer = document.getElementById('idParamContainer');
        this.idParam = document.getElementById('idParam');
        this.navList = document.getElementById('navList');

        this.init();
    }

    init() {
        if (!this.modal) return;

        this.initRouteHandler();
        this.initEditButtons();
        this.initDeleteButtons();
        this.initSaveButton();
        this.initAddButton();
        this.initDragAndDrop();
    }

    /* ================= ROUTE ================= */

    initRouteHandler() {
        if (!this.routeSelect) return;
        this.routeSelect.addEventListener('change', () => this.checkRouteParams());
    }

    checkRouteParams() {
        const selectedRoute = this.routeSelect.value;

        if (selectedRoute.includes('@id')) {
            this.idParamContainer.style.display = 'block';
            this.idParam.required = true;

            const match = selectedRoute.match(/(.+\/)(\d+)$/);
            if (match?.[2]) {
                this.idParam.value = match[2];
            }
        } else {
            this.idParamContainer.style.display = 'none';
            this.idParam.required = false;
            this.idParam.value = '';
        }
    }

    /* ================= EDIT ================= */

    initEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', e => this.handleEdit(e));
        });
    }

    async handleEdit(e) {
        const id = e.currentTarget.closest('tr').dataset.id;

        try {
            const response = await fetch(`/api/navbar/getItem/${id}`);
            const data = await response.json();

            if (!data.success) {
                alert("Erreur : " + data.message);
                return;
            }

            const item = data.data.item;

            document.getElementById('itemId').value = item.Id;
            document.getElementById('itemName').value = item.Name;

            let routeBase = item.Route;
            let idValue = '';

            const match = item.Route.match(/(.+\/)(\d+)$/);
            if (match) {
                routeBase = match[1] + '@id';
                idValue = match[2];
            }

            [...this.routeSelect.options].forEach((opt, i) => {
                if (opt.value === routeBase) {
                    this.routeSelect.selectedIndex = i;
                }
            });

            this.idParam.value = idValue;
            this.checkRouteParams();

            const groupSelect = document.getElementById('itemGroup');
            const groupId = item.IdGroup ? item.IdGroup.toString() : '';
            [...groupSelect.options].forEach((opt, i) => {
                if (opt.value === groupId) {
                    groupSelect.selectedIndex = i;
                }
            });

            document.getElementById('forMembers').checked = item.ForMembers == 1;
            document.getElementById('forAnonymous').checked = item.ForAnonymous == 1;

            this.modal.show();

        } catch (error) {
            alert('Failed to load navigation item data: ' + error.message);
        }
    }

    /* ================= DELETE ================= */

    initDeleteButtons() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', e => this.handleDelete(e));
        });
    }

    async handleDelete(e) {
        const row = e.currentTarget.closest('tr');
        const id = row.dataset.id;

        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément de navigation ?')) return;

        try {
            const response = await fetch(`/api/navBar/deleteItem/${id}`, { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                row.remove();
            } else {
                alert(result.message || 'Échec de la suppression.');
            }
        } catch (error) {
            alert('Erreur lors de la suppression.');
        }
    }

    /* ================= SAVE ================= */

    initSaveButton() {
        document.getElementById('saveChanges')
            ?.addEventListener('click', () => this.handleSave());
    }

    async handleSave() {
        const name = document.getElementById('itemName').value.trim();
        let route = this.routeSelect.value;

        if (!name || !route) {
            alert('Name and Route are required fields.');
            return;
        }

        if (route.includes('@id')) {
            const paramValue = this.idParam.value.trim();
            if (!paramValue) {
                alert('ID Parameter is required.');
                return;
            }
            route = route.replace('@id', paramValue);
        }

        const data = {
            id: document.getElementById('itemId').value,
            name,
            route,
            idGroup: document.getElementById('itemGroup').value || null,
            forMembers: document.getElementById('forMembers').checked ? 1 : 0,
            forAnonymous: document.getElementById('forAnonymous').checked ? 1 : 0
        };

        try {
            const response = await fetch('/api/navbar/saveItem', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.modal.hide();
                location.reload();
            } else {
                alert(result.message || 'Failed to save.');
            }

        } catch (error) {
            alert('Error while saving.');
        }
    }

    /* ================= ADD ================= */

    initAddButton() {
        document.getElementById('addNew')
            ?.addEventListener('click', () => this.handleAdd());
    }

    handleAdd() {
        document.getElementById('itemId').value = '';
        document.getElementById('itemName').value = '';
        this.routeSelect.selectedIndex = 0;
        document.getElementById('itemGroup').selectedIndex = 0;
        document.getElementById('forMembers').checked = false;
        document.getElementById('forAnonymous').checked = false;
        this.idParam.value = '';
        this.idParamContainer.style.display = 'none';

        this.modal.show();
    }

    /* ================= DRAG & DROP ================= */

    initDragAndDrop() {
        if (!this.navList) return;

        let draggedRow = null;

        this.navList.querySelectorAll('tr').forEach(row => {
            row.draggable = true;

            row.addEventListener('dragstart', e => {
                draggedRow = row;
                row.classList.add('table-active');
                e.dataTransfer.effectAllowed = 'move';
            });

            row.addEventListener('dragend', () => {
                row.classList.remove('table-active');
                draggedRow = null;
            });

            row.addEventListener('dragover', e => {
                e.preventDefault();
            });

            row.addEventListener('drop', e => {
                e.preventDefault();
                if (!draggedRow || draggedRow === row) return;

                const rows = [...this.navList.querySelectorAll('tr')];
                const draggedIndex = rows.indexOf(draggedRow);
                const targetIndex = rows.indexOf(row);

                if (draggedIndex < targetIndex) {
                    this.navList.insertBefore(draggedRow, row.nextSibling);
                } else {
                    this.navList.insertBefore(draggedRow, row);
                }

                this.updatePositions();
            });
        });
    }

    async updatePositions() {
        const positions = {};
        this.navList.querySelectorAll('tr').forEach((row, index) => {
            positions[row.dataset.id] = index + 1;
        });

        try {
            const response = await fetch('/api/navbar/updatePositions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ positions })
            });

            const result = await response.json();
            if (!result.success) {
                alert('Erreur mise à jour positions : ' + result.message);
            }

        } catch (error) {
            alert('Erreur mise à jour positions.');
        }
    }
}