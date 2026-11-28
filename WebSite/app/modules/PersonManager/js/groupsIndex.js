import ApiClient from '../../Common/js/ApiClient.js';
import { t } from '/app/modules/Common/js/i18n.js';

const api = new ApiClient('/api');

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-load-users').forEach(btn => {
        btn.addEventListener('click', async () => {
            await loadGroupUsers(btn.dataset.groupId);
        });
    });

    document.querySelectorAll('.btn-confirm-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            confirmDelete(btn.dataset.groupId);
        });
    });
});

function showMessage(container, text, cssClass = '') {
    const p = document.createElement('p');
    if (cssClass !== '') {
        p.className = cssClass;
    }
    p.textContent = text;
    container.replaceChildren(p);
}

function renderUsers(container, users) {
    const list = document.createElement('ul');
    list.className = 'list-unstyled mb-0';
    for (const user of users) {
        const item = document.createElement('li');
        // textContent: user data is never interpreted as HTML
        item.textContent = `${user.FirstName} ${user.LastName} (${user.Email})`;
        list.appendChild(item);
    }
    container.replaceChildren(list);
}

async function loadGroupUsers(groupId) {
    const userRow = document.getElementById(`users-group-${groupId}`);
    const userList = document.getElementById(`user-list-${groupId}`);

    if (!userRow || !userList) {
        console.error('Missing DOM elements for group', groupId);
        return;
    }

    if (!userRow.classList.contains('d-none')) {
        userRow.classList.add('d-none');
        return;
    }

    userList.textContent = t('person_manager.group.loading');

    try {
        const response = await api.get(`/api/personsInGroup/${groupId}`);
        if (response.success && Array.isArray(response.data.items)) {
            if (response.data.items.length === 0) {
                showMessage(userList, t('person_manager.group.js.no_users'));
            } else {
                renderUsers(userList, response.data.items);
            }
        } else {
            showMessage(userList, t('person_manager.group.js.unexpected_response'), 'text-warning');
        }
        userRow.classList.remove('d-none');
    } catch (err) {
        console.error(err);
        showMessage(userList, t('person_manager.group.js.load_error'), 'text-danger');
    }
}

function confirmDelete(groupId) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/group/delete/${groupId}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
