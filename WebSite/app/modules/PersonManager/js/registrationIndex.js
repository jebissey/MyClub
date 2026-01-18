import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient('');

let groupsModal = null;

function initGroups() {
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('groupsModal');
        if (!modalEl) {
            console.warn('groupsModal introuvable');
            return;
        }
        groupsModal = new bootstrap.Modal(modalEl);
    });
}

async function showGroups(personId) {
    try {
        const response = await fetch(`/registration/groups/${personId}`);
        const html = await response.text();

        document.getElementById('groupsContent').innerHTML = html;
        groupsModal?.show();

    } catch (err) {
        alert('Impossible de charger les groupes : ' + err.message);
    }
}

async function addToGroup(personId, groupId) {
    const result = await api.post(
        `/api/registration/add/${personId}/${groupId}`,
        {}
    );

    if (result.success) {
        await showGroups(personId);
    } else {
        alert(result.message || 'Une erreur est survenue');
    }
}

async function removeFromGroup(personId, groupId) {
    const result = await api.post(
        `/api/registration/remove/${personId}/${groupId}`,
        {}
    );

    if (result.success) {
        await showGroups(personId);
    } else {
        alert(result.message || 'Une erreur est survenue');
    }
}

initGroups();

window.showGroups = showGroups;
window.addToGroup = addToGroup;
window.removeFromGroup = removeFromGroup;
