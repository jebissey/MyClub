export default class MenuItemManager {
    constructor() {
        this.modalEl = document.getElementById('editModal');

        this.lists = {
            navbar: document.getElementById('navList'),
            sidebar: document.getElementById('sidebarList'),
        };

        this.init();
    }

    init() {
        this.initModalFields();   // ← nouveau
        this.initEditButtons();
        this.initDeleteButtons();
        this.initSaveButton();
        this.initAddButtons();

        Object.values(this.lists).forEach(list => {
            if (list) this.initDragAndDrop(list);
        });
    }

    get modal() {
        return bootstrap.Modal.getOrCreateInstance(this.modalEl);
    }

    /* ================= MODAL FIELDS ================= */

    initModalFields() {
        // Changement de type → recalcul visibilité
        document.getElementById('itemType')
            .addEventListener('change', () => this.refreshFields());

        // Prévisualisation icône
        document.getElementById('itemIcon')
            .addEventListener('input', function () {
                document.getElementById('iconPreview').className = 'bi ' + this.value.trim();
            });

        // Exposé globalement pour les boutons add/edit
        window.openMenuItemModal = (item = null, what = 'navbar') => {
            document.getElementById('itemId').value = item?.Id ?? '';
            document.getElementById('itemWhat').value = what;
            document.getElementById('itemType').value = item?.Type ?? 'link';
            document.getElementById('itemLabel').value = item?.Label ?? '';
            document.getElementById('itemIcon').value = item?.Icon ?? '';
            document.getElementById('itemUrl').value = item?.Url ?? '';
            document.getElementById('itemParent').value = item?.ParentId ?? '';
            document.getElementById('itemGroup').value = item?.IdGroup ?? '';
            document.getElementById('forMembers').checked = !!item?.ForMembers;
            document.getElementById('forContacts').checked = !!item?.ForContacts;
            document.getElementById('forAnonymous').checked = !!item?.ForAnonymous;

            document.getElementById('iconPreview').className = 'bi ' + (item?.Icon ?? '');
            document.getElementById('editModalTitle').textContent =
                item ? 'Modifier un élément' : 'Ajouter un élément';

            this.refreshFields();
            this.modal.show();
        };
    }

    refreshFields() {
        const what = document.getElementById('itemWhat').value;
        const type = document.getElementById('itemType').value;
        const isSidebar = what === 'sidebar';

        this.modalEl.querySelectorAll('.field-navbar').forEach(el =>
            el.classList.toggle('d-none', isSidebar));

        this.modalEl.querySelectorAll('.field-sidebar').forEach(el =>
            el.classList.toggle('d-none', !isSidebar));

        // URL : toujours visible en navbar, visible en sidebar seulement si type=link
        this.modalEl.querySelectorAll('.field-sidebar-link').forEach(el =>
            el.classList.toggle('d-none', isSidebar && type !== 'link'));

        if (isSidebar) {
            this.modalEl.querySelectorAll('.field-hide-divider').forEach(el =>
                el.classList.toggle('d-none', type === 'divider'));

            this.modalEl.querySelectorAll('.field-hide-heading').forEach(el =>
                el.classList.toggle('d-none', type === 'heading'));
        }
    }

    /* ================= EDIT ================= */

    initEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', e => this.handleEdit(e));
        });
    }

    async handleEdit(e) {
        const id = e.currentTarget.closest('tr').dataset.id;

        try {
            const response = await fetch(`/api/menuItem/get/${id}`);
            const data = await response.json();

            if (!data.success) {
                alert('Erreur : ' + data.message);
                return;
            }

            window.openMenuItemModal(data.data.item, data.data.item.What);

        } catch (error) {
            alert('Erreur lors du chargement : ' + error.message);
        }
    }

    /* ================= DELETE ================= */

    initDeleteButtons() {
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', e => this.handleDelete(e));
        });
    }

    async handleDelete(e) {
        const row = e.currentTarget.closest('tr');
        const id = row.dataset.id;

        if (!confirm('Supprimer cet élément ?')) return;

        try {
            const response = await fetch(`/api/menuItem/delete/${id}`, { method: 'POST' });
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
        const what = document.getElementById('itemWhat').value;
        const type = document.getElementById('itemType').value;
        const label = document.getElementById('itemLabel').value.trim();
        const url = document.getElementById('itemUrl').value.trim();

        if (what === 'navbar' && !label) {
            alert('Le label est requis.');
            return;
        }
        if (what === 'sidebar' && type === 'link' && !url) {
            alert("L'URL est requise pour un lien.");
            return;
        }

        const data = {
            id: document.getElementById('itemId').value || null,
            what,
            type,
            label: label || null,
            icon: document.getElementById('itemIcon').value.trim() || null,
            url: url || null,
            parentId: document.getElementById('itemParent').value || null,
            idGroup: document.getElementById('itemGroup').value || null,
            forMembers: document.getElementById('forMembers').checked ? 1 : 0,
            forContacts: document.getElementById('forContacts').checked ? 1 : 0,
            forAnonymous: document.getElementById('forAnonymous').checked ? 1 : 0,
        };

        try {
            const response = await fetch('/api/menuItem/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (result.success) {
                this.modal.hide();
                location.reload();
            } else {
                alert(result.message || 'Échec de la sauvegarde.');
            }
        } catch (error) {
            alert('Erreur lors de la sauvegarde : ' + error.message);
        }
    }

    /* ================= ADD ================= */

    initAddButtons() {
        document.querySelectorAll('.add-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                const what = e.currentTarget.dataset.what;
                window.openMenuItemModal(null, what);
            });
        });
    }

    /* ================= DRAG & DROP ================= */

    initDragAndDrop(list) {
        let draggedRow = null;

        list.querySelectorAll('tr').forEach(row => {
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

            row.addEventListener('dragover', e => e.preventDefault());

            row.addEventListener('drop', e => {
                e.preventDefault();
                if (!draggedRow || draggedRow === row) return;

                const rows = [...list.querySelectorAll('tr')];
                if (rows.indexOf(draggedRow) < rows.indexOf(row)) {
                    list.insertBefore(draggedRow, row.nextSibling);
                } else {
                    list.insertBefore(draggedRow, row);
                }

                this.updatePositions(list);
            });
        });
    }

    async updatePositions(list) {
        const positions = {};
        list.querySelectorAll('tr').forEach((row, index) => {
            positions[row.dataset.id] = index + 1;
        });

        try {
            const response = await fetch('/api/menuitem/updatePositions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ positions }),
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