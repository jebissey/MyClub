import ApiClient from '../../Common/js/ApiClient.js';

export default class MenuItemManager {
    constructor() {
        this.api = new ApiClient();
        this.modalEl = document.getElementById('editModal');

        this.lists = {
            navbar: document.getElementById('navList'),
            sidebar: document.getElementById('sidebarList'),
        };

        this.init();
    }

    init() {
        this.initModalFields();
        this.initEditButtons();
        this.initDeleteButtons();
        this.initSaveButton();
        this.initAddButtons();
        this.initPreview();

        Object.values(this.lists).forEach(list => {
            if (list) this.initDragAndDrop(list);
        });
    }

    get modal() {
        return bootstrap.Modal.getOrCreateInstance(this.modalEl);
    }

    /* ================= MODAL FIELDS ================= */

    initModalFields() {
        document.getElementById('itemType')
            .addEventListener('change', () => this.refreshFields());

        document.getElementById('itemIcon')
            .addEventListener('input', function () {
                document.getElementById('iconPreview').className = 'bi ' + this.value.trim();
            });

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
                item ? window.t('edit_item') : window.t('add_item');

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

        const data = await this.api.get(`/api/menuItem/get/${id}`);

        if (!data.success) {
            alert(`${window.t('error')} ${data.message}`);
            return;
        }

        window.openMenuItemModal(data.data.item, data.data.item.What);
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

        if (!confirm(window.t('delete_confirm'))) return;

        const result = await this.api.post(`/api/menuItem/delete/${id}`);

        if (result.success) {
            row.remove();
        } else {
            alert(result.message || window.t('delete_failed'));
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
            alert(window.t('label_required'));
            return;
        }
        if (what === 'sidebar' && type === 'link' && !url) {
            alert(window.t('url_required'));
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

        const result = await this.api.post('/api/menuItem/save', data);

        if (result.success) {
            this.modal.hide();
            location.reload();
        } else {
            alert(result.message || window.t('save_failed'));
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
        const orderedIds = [];

        list.querySelectorAll('tr').forEach((row, index) => {
            const id = Number(row.dataset.id);
            positions[id] = index + 1;
            orderedIds.push(id);
        });

        const result = await this.api.post('/api/menuitem/updatePositions', { positions });

        if (result.success) {
            // re-trier previewItems selon le nouvel ordre du DOM
            this.previewItems.sort((a, b) =>
                orderedIds.indexOf(a.Id) - orderedIds.indexOf(b.Id)
            );
            this.renderPreview();
        } else {
            alert(`${window.t('positions_error')} ${result.message}`);
        }
    }

    /* ================= PRÉVISUALISATION NAVBAR ================= */

    initPreview() {
        this.previewNavEl = document.getElementById('navbarPreview');
        this.previewSidebarEl = document.getElementById('sidebarPreview');

        this.previewItems = Object.values(window.navbarItemsData ?? {});
        this.previewSideItems = Object.values(window.sidebarItemsData ?? {});

        if (!this.previewNavEl && !this.previewSidebarEl) return;

        const controls = [
            ...document.querySelectorAll('input[name="previewRole"]'),
            document.getElementById('previewGroup'),
        ];
        controls.forEach(el => el?.addEventListener('change', () => this.renderPreviews()));

        const defaultRadio = document.getElementById('previewMembers');
        if (defaultRadio) defaultRadio.checked = true;

        this.renderPreviews();
    }

    renderPreviews() {
        this.renderNavbarPreview();
        this.renderSidebarPreview();
    }

    renderNavbarPreview() {
        if (!this.previewNavEl) return;

        const role = document.querySelector('input[name="previewRole"]:checked')?.value ?? '';
        const groupId = document.getElementById('previewGroup')?.value ?? '';

        const visible = this.previewItems.filter(item => this.isVisible(item, role, groupId));

        if (!visible.length) {
            this.previewNavEl.innerHTML =
                '<li class="nav-item"><span class="nav-link text-muted fst-italic small">— aucun élément visible —</span></li>';
            return;
        }

        this.previewNavEl.innerHTML = visible.map(item => `
        <li class="nav-item">
            <a class="nav-link py-1" href="${item.Url ?? '#'}" tabindex="-1">
                ${item.Label ?? ''}
            </a>
        </li>`
        ).join('');
    }

    renderSidebarPreview() {
        if (!this.previewSidebarEl) return;

        const role = document.querySelector('input[name="previewRole"]:checked')?.value ?? '';
        const groupId = document.getElementById('previewGroup')?.value ?? '';

        const visible = this.previewSideItems.filter(item => this.isVisible(item, role, groupId));

        if (!visible.length) {
            this.previewSidebarEl.innerHTML =
                '<p class="text-muted fst-italic small px-2 py-2 mb-0">— aucun élément —</p>';
            return;
        }

        const roots = visible.filter(i => !i.ParentId);
        const html = roots.map(item => this.renderSidebarItem(item, visible)).join('');
        this.previewSidebarEl.innerHTML = `<ul class="list-unstyled mb-0 py-1">${html}</ul>`;
    }

    renderSidebarItem(item, all) {
        const children = all.filter(i => i.ParentId === item.Id);
        const icon = item.Icon ? `<i class="bi ${item.Icon} me-1"></i>` : '';

        if (item.Type === 'divider') {
            return '<li><hr class="my-1 mx-2"></li>';
        }
        if (item.Type === 'heading') {
            return `<li class="px-3 pt-2 pb-1 text-muted small fw-bold text-uppercase" style="font-size:.7rem">${icon}${item.Label ?? ''}</li>`;
        }
        if (children.length) {
            const childHtml = children.map(c => this.renderSidebarItem(c, all)).join('');
            return `
            <li class="px-3 py-1 small fw-semibold">${icon}${item.Label ?? ''}</li>
            <li><ul class="list-unstyled ps-2">${childHtml}</ul></li>`;
        }
        return `<li>
        <a class="d-flex align-items-center px-3 py-1 text-decoration-none text-body small" href="${item.Url ?? '#'}" tabindex="-1">
            ${icon}${item.Label ?? ''}
        </a>
    </li>`;
    }

    isVisible(item, role, groupId) {
        if (item.IdGroup) return groupId !== '' && String(item.IdGroup) === groupId;
        if (role === 'members') return !!item.ForMembers;
        if (role === 'contacts') return !!item.ForContacts;
        if (role === 'anonymous') return !!item.ForAnonymous;
        return false;
    }
}