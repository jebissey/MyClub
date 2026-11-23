/** Converts a raw server item (camelCase keys) into the shape used by the preview. */
const normalize = raw => ({
    id:           String(raw.id ?? ''),
    what:         raw.what ?? null,
    type:         raw.type ?? null,
    icon:         raw.icon ?? null,
    label:        raw.label ?? '',
    url:          raw.url ?? '#',
    parentId:     raw.parentId ?? null,
    groupId:      raw.idGroup ?? raw.groupId ?? null,
    forMembers:   !!raw.forMembers,
    forContacts:  !!raw.forContacts,
    forAnonymous: !!raw.forAnonymous,
});

/** Escapes a string so it can be safely inserted into HTML text or attribute values. */
const escapeHtml = value => String(value).replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[c]));

/**
 * Labels may contain HTML entered by the designer (e.g. "<h2>Contact</h2>").
 * Keep only the text content, then escape it.
 */
const toSafeText = html => {
    const text = new DOMParser().parseFromString(String(html), 'text/html').body.textContent ?? '';
    return escapeHtml(text);
};

/** Translates a key, falling back to a neutral dash when the translation is missing. */
const translate = key => escapeHtml(window.t?.(key) ?? '—');

export default class MenuPreview {
    constructor(navEl, sidebarEl, navItems, sidebarItems) {
        this.navEl        = navEl;
        this.sidebarEl    = sidebarEl;
        this.navItems     = navItems.map(normalize);
        this.sidebarItems = sidebarItems.map(normalize);
    }

    bindControls() {
        const previewGroup     = document.getElementById('previewGroup');
        const previewMembers   = document.getElementById('previewMembers');
        const previewContacts  = document.getElementById('previewContacts');
        const previewAnonymous = document.getElementById('previewAnonymous');

        previewGroup?.addEventListener('change', () => {
            // Selecting a group implies previewing as a member of that group
            if (previewGroup.value !== '' && previewMembers) previewMembers.checked = true;
            this.render();
        });

        [previewMembers, previewContacts, previewAnonymous].forEach(el => {
            el?.addEventListener('change', () => {
                const role = this._currentRole();
                // Contacts and anonymous visitors cannot belong to a group
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

    /** Applies a drag & drop order to the navbar or sidebar items, then refreshes the preview. */
    reorderItems(what, orderedIds) {
        const ids   = orderedIds.map(String);
        const items = what === 'sidebar' ? this.sidebarItems : this.navItems;
        items.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
        this.render();
    }

    _currentRole() {
        return document.querySelector('input[name="previewRole"]:checked')?.value ?? '';
    }

    _currentGroupId() {
        return document.getElementById('previewGroup')?.value ?? '';
    }

    isVisible(item, role, groupId) {
        // An item bound to a group is only visible when that group is selected
        if (item.groupId) return groupId !== '' && String(item.groupId) === groupId;
        if (role === 'members')   return item.forMembers;
        if (role === 'contacts')  return item.forContacts;
        if (role === 'anonymous') return item.forAnonymous;
        return false;
    }

    _visibleItems(items) {
        const role    = this._currentRole();
        const groupId = this._currentGroupId();
        return items.filter(item => this.isVisible(item, role, groupId));
    }

    _renderNavbar() {
        if (!this.navEl) return;
        const visible = this._visibleItems(this.navItems);

        this.navEl.innerHTML = visible.length
            ? visible.map(item => `
                <li class="nav-item">
                    <a class="nav-link py-1" href="${escapeHtml(item.url)}" tabindex="-1">
                        ${toSafeText(item.label)}
                    </a>
                </li>`).join('')
            : `<li class="nav-item"><span class="nav-link text-muted fst-italic small">${translate('preview_empty_navbar')}</span></li>`;
    }

    _renderSidebar() {
        if (!this.sidebarEl) return;
        const visible = this._visibleItems(this.sidebarItems);

        if (!visible.length) {
            this.sidebarEl.innerHTML =
                `<p class="text-muted fst-italic small px-2 py-2 mb-0">${translate('preview_empty_sidebar')}</p>`;
            return;
        }

        const roots = visible.filter(i => !i.parentId);
        const html  = roots.map(item => this._renderItem(item, visible)).join('');
        this.sidebarEl.innerHTML = `<ul class="list-unstyled mb-0 py-1">${html}</ul>`;
    }

    _renderItem(item, all) {
        const children = all.filter(i => String(i.parentId) === item.id);
        const icon     = item.icon ? `<i class="bi ${escapeHtml(item.icon)} me-1"></i>` : '';
        const label    = toSafeText(item.label);

        if (item.type === 'divider') return '<li><hr class="my-1 mx-2"></li>';
        if (item.type === 'heading') {
            return `<li class="px-3 pt-2 pb-1 text-muted small fw-bold text-uppercase" style="font-size:.7rem">${icon}${label}</li>`;
        }

        if (children.length) {
            const childHtml = children.map(c => this._renderItem(c, all)).join('');
            return `<li class="px-3 py-1 small fw-semibold">${icon}${label}</li>
                    <li><ul class="list-unstyled ps-2">${childHtml}</ul></li>`;
        }

        return `<li>
            <a class="d-flex align-items-center px-3 py-1 text-decoration-none text-body small"
               href="${escapeHtml(item.url)}" tabindex="-1">${icon}${label}
            </a>
        </li>`;
    }
}