document.addEventListener("DOMContentLoaded", function () {
    loadAttributesList();
});

function createAttribute_() {
    const name = document.getElementById('newAttributeName').value;
    const detail = document.getElementById('newAttributeDetail').value;
    const color = document.getElementById('newAttributeColor').value;

    if (!name) {
        alert('Le nom de l\'attribut est requis');
        return;
    }

    fetch('/api/attributes/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name, detail, color })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadAttributesList();
                document.getElementById('newAttributeName').value = '';
                document.getElementById('newAttributeDetail').value = '';
                document.getElementById('newAttributeColor').value = '#563d7c';
            } else {
                alert('Une erreur est survenue (1) : ' + data.message);
            }
        })
        .catch(error => {
            alert('Une erreur est survenue (2) : ' + error.message);
        });
}

function editAttribute(id) {
    const name = document.getElementById(`attributeName${id}`).value;
    const detail = document.getElementById(`attributeDetail${id}`).value;
    const color = document.getElementById(`attributeColor${id}`).value;

    fetch('/api/attributes/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id, name, detail, color })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadAttributesList();
            } else {
                alert('Une erreur est survenue 3) : ' + data.message);
            }
        })
        .catch(error => {
            alert('Une erreur est survenue (4) : ' + error.message);
        });
}

function deleteAttribute(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet attribut ?')) {
        fetch(`/api/attributes/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) loadAttributesList();
                else alert('Une erreur est survenue (5) : ' + data.message);
            })
            .catch(error => {
                alert('Une erreur est survenue(6) : ' + error.message);
            });
    }
}

function loadAttributesList() {
    fetch('/api/attributes/list')
        .then(response => response.text())
        .then(html => {
            document.getElementById('attributesList').innerHTML = html;
        })
        .catch(error => {
            alert('Impossible de charger la liste des attributs : ' + error.message);
        });
}