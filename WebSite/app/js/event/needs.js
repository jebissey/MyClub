document.addEventListener('DOMContentLoaded', function () {
    let deleteItemId = null;
    let deleteItemType = null;
    const needTypeModal = new bootstrap.Modal(document.getElementById('addNeedTypeModal'));
    const needTypeForm = document.getElementById('needTypeForm');
    const needModal = new bootstrap.Modal(document.getElementById('addNeedModal'));
    const needForm = document.getElementById('needForm');
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

    document.querySelector('[data-bs-target="#addNeedTypeModal"]').addEventListener('click', function () {
        document.getElementById('needTypeModalTitle').textContent = 'Ajouter un type de besoin';
        needTypeForm.reset();
        document.getElementById('needTypeId').value = '';
    });

    document.querySelectorAll('.edit-need-type').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('needTypeModalTitle').textContent = 'Modifier un type de besoin';
            document.getElementById('needTypeId').value = this.dataset.id;
            document.getElementById('needTypeName').value = this.dataset.name;
            needTypeModal.show();
        });
    });

    document.querySelector('[data-bs-target="#addNeedModal"]').addEventListener('click', function () {
        document.getElementById('needModalTitle').textContent = 'Ajouter un besoin';
        needForm.reset();
        document.getElementById('needId').value = '';
    });

    document.querySelectorAll('.edit-need').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('needModalTitle').textContent = 'Modifier un besoin';
            document.getElementById('needId').value = this.dataset.id;
            document.getElementById('needLabel').value = this.dataset.label;
            document.getElementById('needName').value = this.dataset.name;
            document.getElementById('needParticipantDependent').checked = this.dataset.participantDependent;
            document.getElementById('needType').value = this.dataset.type;
            needModal.show();
        });
    });

    document.getElementById('saveNeedType').addEventListener('click', function () {
        const data = {
            id: document.getElementById('needTypeId').value,
            name: document.getElementById('needTypeName').value
        };
        fetch('/api/needs/type/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    bootstrap.Modal.getInstance(document.getElementById('addNeedTypeModal')).hide();
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'enregistrement');
            });
    });

    document.getElementById('saveNeed').addEventListener('click', function () {
        const data = {
            id: document.getElementById('needId').value,
            label: document.getElementById('needLabel').value,
            name: document.getElementById('needName').value,
            idNeedType: document.getElementById('needType').value,
            participantDependent: document.getElementById('participantDependent').checked ? 1 : 0
        };

        fetch('/api/needs/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    bootstrap.Modal.getInstance(document.getElementById('addNeedModal')).hide();
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'enregistrement');
            });
    });

    document.querySelectorAll('.delete-need-type').forEach(button => {
        button.addEventListener('click', function () {
            deleteItemId = this.dataset.id;
            deleteItemType = 'type';
            document.getElementById('deleteConfirmMessage').textContent =
                `Êtes-vous sûr de vouloir supprimer le type de besoin "${this.dataset.name}" ?`;
            deleteConfirmModal.show();
        });
    });

    document.querySelectorAll('.delete-need').forEach(button => {
        button.addEventListener('click', function () {
            deleteItemId = this.dataset.id;
            deleteItemType = 'need';
            document.getElementById('deleteConfirmMessage').textContent =
                `Êtes-vous sûr de vouloir supprimer le besoin "${this.dataset.name}" ?`;
            deleteConfirmModal.show();
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', function () {
        const endpoint = deleteItemType === 'type' ? '/api/needs/type/delete/' : '/api/needs/delete/';
        fetch(endpoint + deleteItemId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    deleteConfirmModal.hide();
                    window.location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression');
            });
    });
});