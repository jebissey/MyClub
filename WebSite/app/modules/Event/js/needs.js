document.addEventListener('DOMContentLoaded', function () {
    const i18n = window.needsI18n;
    const withName = (text, name) => text.replace('{name}', name);

    let deleteItemId = null;
    let deleteItemType = null;
    const needTypeModal = new bootstrap.Modal(document.getElementById('addNeedTypeModal'));
    const needTypeForm = document.getElementById('needTypeForm');
    const needModal = new bootstrap.Modal(document.getElementById('addNeedModal'));
    const needForm = document.getElementById('needForm');
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

    function postJson(url, payload, modalToHide, fallbackError) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: payload === null ? undefined : JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    modalToHide.hide();
                    window.location.reload();
                } else {
                    alert(`${i18n.errorPrefix} ${data.message || i18n.genericError}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(fallbackError);
            });
    }

    document.querySelector('[data-bs-target="#addNeedTypeModal"]').addEventListener('click', function () {
        document.getElementById('needTypeModalTitle').textContent = i18n.addTypeTitle;
        needTypeForm.reset();
        document.getElementById('needTypeId').value = '';
    });

    document.querySelectorAll('.edit-need-type').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('needTypeModalTitle').textContent = i18n.editTypeTitle;
            document.getElementById('needTypeId').value = this.dataset.id;
            document.getElementById('needTypeName').value = this.dataset.name;
            needTypeModal.show();
        });
    });

    document.querySelector('[data-bs-target="#addNeedModal"]').addEventListener('click', function () {
        document.getElementById('needModalTitle').textContent = i18n.addNeedTitle;
        needForm.reset();
        document.getElementById('needId').value = '';
    });

    document.querySelectorAll('.edit-need').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('needModalTitle').textContent = i18n.editNeedTitle;
            document.getElementById('needId').value = this.dataset.id;
            document.getElementById('needLabel').value = this.dataset.label;
            document.getElementById('needName').value = this.dataset.name;
            document.getElementById('needParticipantDependent').checked = this.dataset.participantDependent === '1';
            document.getElementById('needType').value = this.dataset.type;
            needModal.show();
        });
    });

    document.getElementById('saveNeedType').addEventListener('click', function () {
        postJson('/api/need/type/save', {
            id: document.getElementById('needTypeId').value,
            name: document.getElementById('needTypeName').value
        }, needTypeModal, i18n.saveError);
    });

    document.getElementById('saveNeed').addEventListener('click', function () {
        postJson('/api/need/save', {
            id: document.getElementById('needId').value,
            label: document.getElementById('needLabel').value,
            name: document.getElementById('needName').value,
            idNeedType: document.getElementById('needType').value,
            participantDependent: document.getElementById('needParticipantDependent').checked ? 1 : 0
        }, needModal, i18n.saveError);
    });

    document.querySelectorAll('.delete-need-type').forEach(button => {
        button.addEventListener('click', function () {
            deleteItemId = this.dataset.id;
            deleteItemType = 'type';
            document.getElementById('deleteConfirmMessage').textContent =
                withName(i18n.deleteTypeConfirm, this.dataset.name);
            deleteConfirmModal.show();
        });
    });

    document.querySelectorAll('.delete-need').forEach(button => {
        button.addEventListener('click', function () {
            deleteItemId = this.dataset.id;
            deleteItemType = 'need';
            document.getElementById('deleteConfirmMessage').textContent =
                withName(i18n.deleteNeedConfirm, this.dataset.name);
            deleteConfirmModal.show();
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', function () {
        const endpoint = deleteItemType === 'type' ? '/api/need/type/delete/' : '/api/need/delete/';
        postJson(endpoint + deleteItemId, null, deleteConfirmModal, i18n.deleteError);
    });
});