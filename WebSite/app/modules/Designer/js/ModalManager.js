export default class ModalManager {
    constructor(modalEl) {
        this.modalEl = modalEl;
    }

    get instance() {
        return bootstrap.Modal.getOrCreateInstance(this.modalEl);
    }

    show() { this.instance.show(); }
    hide() { this.instance.hide(); }

    initFields(onTypeChange) {
        document.getElementById('itemType')
            .addEventListener('change', onTypeChange);

        document.getElementById('itemIcon')
            .addEventListener('input', function () {
                document.getElementById('iconPreview').className =
                    'bi ' + this.value.trim();
            });
    }

    populate(item = null, what = 'navbar') {
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

        document.getElementById('iconPreview').className =
            'bi ' + (item?.Icon ?? '');
        document.getElementById('editModalTitle').textContent =
            item ? window.t('edit_item') : window.t('add_item');
    }

    refreshFields() {
        const what = document.getElementById('itemWhat').value;
        const type = document.getElementById('itemType').value;
        const isSidebar = what === 'sidebar';

        this.modalEl.querySelectorAll('.field-navbar:not(.field-sidebar)')
            .forEach(el => el.classList.toggle('d-none', isSidebar));

        this.modalEl.querySelectorAll('.field-sidebar:not(.field-navbar)')
            .forEach(el => el.classList.toggle('d-none', !isSidebar));

        this.modalEl.querySelectorAll('.field-navbar.field-sidebar')
            .forEach(el => el.classList.remove('d-none'));

        this.modalEl.querySelectorAll('.field-sidebar-link')
            .forEach(el => el.classList.toggle('d-none', isSidebar && type !== 'link'));

        if (isSidebar) {
            this.modalEl.querySelectorAll('.field-hide-divider')
                .forEach(el => el.classList.toggle('d-none', type === 'divider'));
            this.modalEl.querySelectorAll('.field-hide-heading')
                .forEach(el => el.classList.toggle('d-none', type === 'heading'));
        }
    }
}