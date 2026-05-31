export default class MenuPreview {
    constructor(navEl, sidebarEl, navItems, sidebarItems) {
        this.navEl      = navEl;
        this.sidebarEl  = sidebarEl;
        this.navItems   = navItems;
        this.sidebarItems = sidebarItems;
    }

    bindControls() {
        const previewGroup     = document.getElementById('previewGroup');
        const previewMembers   = document.getElementById('previewMembers');
        const previewContacts  = document.getElementById('previewContacts');
        const previewAnonymous = document.getElementById('previewAnonymous');

        previewGroup?.addEventListener('change', () => {
            if (previewGroup.value !== '') previewMembers.checked = true;
            this.render();
        });

        [previewMembers, previewContacts, previewAnonymous].forEach(el => {
            el?.addEventListener('change', () => {
                const role = document.querySelector('input[name="previewRole"]:checked')?.value ?? '';
                if ((role === 'contacts' || role === 'anonymous') && previewGroup?.value !== '') {
                    previewGroup.value = '';
                }
                this.render();
            });
        });

        if (previewMembers) previewMembers.checked = true;
        this.render();
    }

    render() {
        this._renderNavbar();
        this._renderSidebar();
    }

    reorderNavItems(orderedIds) {
        this.navItems.sort((a, b) =>
            orderedIds.indexOf(a.Id) - orderedIds.indexOf(b.Id)
        );
        this.render();
    }

    _currentRole() {
        return document.querySelector('input[name="previewRole"]:checked')?.value ?? '';
    }

    _currentGroupId() {
        return document.getElementById('previewGroup')?.value ?? '';
    }

    isVisible(item, role, groupId) {
        if (item.IdGroup) return groupId !== '' && String(item.IdGroup) === groupId;
        if (role === 'members')  return !!item.ForMembers;
        if (role === 'contacts') return !!item.ForContacts;
        if (role === 'anonymous') return !!item.ForAnonymous;
        return false;
    }

    _renderNavbar() {
        if (!this.navEl) return;
        const visible = this.navItems.filter(
            item => this.isVisible(item, this._currentRole(), this._currentGroupId())
        );

        this.navEl.innerHTML = visible.length
            ? visible.map(item => `
                <li class="nav-item">
                    <a class="nav-link py-1" href="${item.Url ?? '#'}" tabindex="-1">
                        ${item.Label ?? ''}
                    </a>
                </li>`).join('')
            : '<li class="nav-item"><span class="nav-link text-muted fst-italic small">— aucun élément visible —</span></li>';
    }

    _renderSidebar() {
        if (!this.sidebarEl) return;
        const visible = this.sidebarItems.filter(
            item => this.isVisible(item, this._currentRole(), this._currentGroupId())
        );

        if (!visible.length) {
            this.sidebarEl.innerHTML =
                '<p class="text-muted fst-italic small px-2 py-2 mb-0">— aucun élément —</p>';
            return;
        }

        const roots = visible.filter(i => !i.ParentId);
        const html  = roots.map(item => this._renderItem(item, visible)).join('');
        this.sidebarEl.innerHTML = `<ul class="list-unstyled mb-0 py-1">${html}</ul>`;
    }

    _renderItem(item, all) {
        const children = all.filter(i => i.ParentId === item.Id);
        const icon     = item.Icon ? `<i class="bi ${item.Icon} me-1"></i>` : '';

        if (item.Type === 'divider')  return '<li><hr class="my-1 mx-2"></li>';
        if (item.Type === 'heading')  return `<li class="px-3 pt-2 pb-1 text-muted small fw-bold text-uppercase" style="font-size:.7rem">${icon}${item.Label ?? ''}</li>`;

        if (children.length) {
            const childHtml = children.map(c => this._renderItem(c, all)).join('');
            return `<li class="px-3 py-1 small fw-semibold">${icon}${item.Label ?? ''}</li>
                    <li><ul class="list-unstyled ps-2">${childHtml}</ul></li>`;
        }

        return `<li>
            <a class="d-flex align-items-center px-3 py-1 text-decoration-none text-body small"
               href="${item.Url ?? '#'}" tabindex="-1">${icon}${item.Label ?? ''}
            </a>
        </li>`;
    }
}