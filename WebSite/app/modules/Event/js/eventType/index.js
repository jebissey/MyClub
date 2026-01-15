import ApiClient from '../../../Common/js/ApiClient.js';

const api = new ApiClient('');

export function initAttributes() {
    document.addEventListener('DOMContentLoaded', () => {
        loadAttributesList();
    });
}

export async function createAttribute() {
    const name = document.getElementById('newAttributeName').value;
    const detail = document.getElementById('newAttributeDetail').value;
    const color = document.getElementById('newAttributeColor').value;

    if (!name) {
        alert("Le nom de l'attribut est requis");
        return;
    }

    const result = await api.post('/api/attribute/create', { name, detail, color });

    if (result.success) {
        await loadAttributesList();
        document.getElementById('newAttributeName').value = '';
        document.getElementById('newAttributeDetail').value = '';
        document.getElementById('newAttributeColor').value = '#563d7c';
    } else {
        alert('Erreur : ' + result.message);
    }
}

export async function editAttribute(id) {
    const name = document.getElementById(`attributeName${id}`).value;
    const detail = document.getElementById(`attributeDetail${id}`).value;
    const color = document.getElementById(`attributeColor${id}`).value;

    const result = await api.post('/api/attribute/update', {
        id,
        name,
        detail,
        color
    });

    if (result.success) {
        await loadAttributesList();
    } else {
        alert('Erreur : ' + result.message);
    }
}

export async function deleteAttribute(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet attribut ?')) {
        return;
    }

    const result = await api.post(`/api/attribute/delete/${id}`, {});

    if (result.success) {
        await loadAttributesList();
    } else {
        alert('Erreur : ' + result.message);
    }
}

export async function loadAttributesList() {
    try {
        const response = await fetch('/api/attributes/list');
        const html = await response.text();
        document.getElementById('attributesList').innerHTML = html;
    } catch (err) {
        alert('Impossible de charger la liste des attributs : ' + err.message);
    }
}
