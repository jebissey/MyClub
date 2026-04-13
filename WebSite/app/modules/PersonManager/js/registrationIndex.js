import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient();

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
        const html = await api.getHtml(`/registration/groups/${personId}`);
        document.getElementById('groupsContent').innerHTML = html;
        groupsModal?.show();
    } catch (err) {
        alert(`${window.t('errorLoadGroups')} : ${err.message}`);
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
        alert(result.message || window.t('errorGeneric'));
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
        alert(result.message || window.t('errorGeneric'));
    }
}

initGroups();

window.showGroups = showGroups;
window.addToGroup = addToGroup;
window.removeFromGroup = removeFromGroup;