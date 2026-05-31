export default class MenuItemFormHandler {
    read() {
        return {
            id:          document.getElementById('itemId').value || null,
            what:        document.getElementById('itemWhat').value,
            type:        document.getElementById('itemType').value,
            label:       document.getElementById('itemLabel').value.trim() || null,
            icon:        document.getElementById('itemIcon').value.trim() || null,
            url:         document.getElementById('itemUrl').value.trim() || null,
            parentId:    document.getElementById('itemParent').value || null,
            idGroup:     document.getElementById('itemGroup').value || null,
            forMembers:  document.getElementById('forMembers').checked ? 1 : 0,
            forContacts: document.getElementById('forContacts').checked ? 1 : 0,
            forAnonymous: document.getElementById('forAnonymous').checked ? 1 : 0,
        };
    }

    validate(data) {
        if (data.what === 'navbar' && !data.label) {
            return window.t('label_required');
        }
        if (data.what === 'sidebar' && data.type === 'link' && !data.url) {
            return window.t('url_required');
        }
        return null;
    }
}