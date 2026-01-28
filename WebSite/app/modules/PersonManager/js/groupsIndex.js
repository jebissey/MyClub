import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient('/api');

document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.btn-load-users').forEach(btn => {
        btn.addEventListener('click', async () => {
            const groupId = btn.dataset.groupId;
            await loadGroupUsers(groupId);
        });
    });

    document.querySelectorAll('.btn-confirm-delete').forEach(btn => {
        btn.addEventListener('click', () => {
            const groupId = btn.dataset.groupId;
            confirmDelete(groupId);
        });
    });

});

async function loadGroupUsers(groupId) {
    const userRow = document.getElementById(`users-group-${groupId}`);
    const userList = document.getElementById(`user-list-${groupId}`);

    if (!userRow || !userList) {
        console.error('Éléments DOM manquants pour le groupe', groupId);
        return;
    }

    if (userRow.classList.contains('d-none')) {
        userList.innerHTML = 'Chargement…';

        try {
            const response = await api.get(`/api/personsInGroup/${groupId}`);
            if (response.success && Array.isArray(response.data.items)) {
                if (response.data.items.length === 0) {
                    userList.innerHTML = '<p>Aucun utilisateur dans ce groupe.</p>';
                } else {
                    userList.innerHTML = `
                        <ul class="list-unstyled mb-0">
                            ${response.data.items.map(u =>
                        `<li>${u.FirstName} ${u.LastName} (${u.Email})</li>`
                    ).join('')}
                        </ul>
                    `;
                }
            } else {
                userList.innerHTML = '<p class="text-warning">Réponse inattendue.</p>';
            }

            userRow.classList.remove('d-none');

        } catch (err) {
            console.error(err);
            userList.innerHTML = `<p class="text-danger">Erreur de chargement.</p>`;
        }

    } else {
        userRow.classList.add('d-none');
    }
}

function confirmDelete(groupId) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/group/delete/${groupId}`;

    const modal = new bootstrap.Modal(
        document.getElementById('deleteModal')
    );
    modal.show();
}
