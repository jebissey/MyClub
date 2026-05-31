import ApiClient            from '../../Common/js/ApiClient.js';
import ModalManager         from './ModalManager.js';
import MenuItemFormHandler  from './MenuItemFormHandler.js';
import MenuItemCrudService  from './MenuItemCrudService.js';
import DragDropManager      from './DragDropManager.js';
import MenuPreview          from './MenuPreview.js';

export default class MenuItemManager {
    constructor() {
        const api       = new ApiClient();
        const modalEl   = document.getElementById('editModal');

        this.modal    = new ModalManager(modalEl);
        this.form     = new MenuItemFormHandler();
        this.crud     = new MenuItemCrudService(api);
        this.dragDrop = new DragDropManager(list => this._handleReorder(list));
        this.preview  = new MenuPreview(
            document.getElementById('navbarPreview'),
            document.getElementById('sidebarPreview'),
            Object.values(window.navbarItemsData  ?? {}),
            Object.values(window.sidebarItemsData ?? {}),
        );

        this._init();
    }

    _init() {
        this.modal.initFields(() => this.modal.refreshFields());

        window.openMenuItemModal = (item = null, what = 'navbar') => {
            this.modal.populate(item, what);
            this.modal.refreshFields();
            this.modal.show();
        };

        document.querySelectorAll('.edit-btn').forEach(btn =>
            btn.addEventListener('click', e => this._handleEdit(e)));

        document.querySelectorAll('.delete-btn').forEach(btn =>
            btn.addEventListener('click', e => this._handleDelete(e)));

        document.querySelectorAll('.add-btn').forEach(btn =>
            btn.addEventListener('click', e =>
                window.openMenuItemModal(null, e.currentTarget.dataset.what)));

        document.getElementById('saveChanges')
            ?.addEventListener('click', () => this._handleSave());

        const lists = {
            navbar:  document.getElementById('navList'),
            sidebar: document.getElementById('sidebarList'),
        };
        Object.values(lists).forEach(list => {
            if (list) this.dragDrop.init(list);
        });

        this.preview.bindControls();
    }

    async _handleEdit(e) {
        const id   = e.currentTarget.closest('tr').dataset.id;
        const data = await this.crud.get(id);
        if (!data.success) { alert(`${window.t('error')} ${data.message}`); return; }
        window.openMenuItemModal(data.data.item, data.data.item.What);
    }

    async _handleDelete(e) {
        const row = e.currentTarget.closest('tr');
        if (!confirm(window.t('delete_confirm'))) return;
        const result = await this.crud.delete(row.dataset.id);
        if (result.success) row.remove();
        else alert(result.message || window.t('delete_failed'));
    }

    async _handleSave() {
        const data  = this.form.read();
        const error = this.form.validate(data);
        if (error) { alert(error); return; }

        const result = await this.crud.save(data);
        if (result.success) { this.modal.hide(); location.reload(); }
        else alert(result.message || window.t('save_failed'));
    }

    async _handleReorder(list) {
        const { positions, orderedIds } = this.dragDrop.extractOrder(list);
        const result = await this.crud.updatePositions(positions);
        if (result.success) this.preview.reorderNavItems(orderedIds);
        else alert(`${window.t('positions_error')} ${result.message}`);
    }
}