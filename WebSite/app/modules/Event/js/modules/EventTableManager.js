import EventFormManager from './EventFormManager.js';

export default class EventTableManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.eventForm = new EventFormManager(this.api);
        this._init();
    }

    _init() {
        document.getElementById('createEventBtn')
            ?.addEventListener('click', () => {
                this.eventForm.openCreateModal();
            });

        const setupEditButtons = () => {
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', e => this.handleEdit(e));
            });
        };

        const setupDeleteButtons = () => {
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', e => this.handleDelete(e));
            });
        };

        const setupDuplicateButtons = () => {
            document.querySelectorAll('.duplicate-btn').forEach(button => {
                button.addEventListener('click', e => this.handleDuplicate(e));
            });
        };

        setupEditButtons();
        setupDeleteButtons();
        setupDuplicateButtons();
    }

    handleEdit(e) {
        e.stopPropagation();

        const getEventIdFromRow = row =>
            row.getAttribute('onclick').match(/\/event\/(\d+)/)[1];

        const fetchAndEdit = async eventId => {
            const response = await this.api.get(`/api/event/${eventId}`);

            if (response.success && response.data.event && response.data.attributes) {
                await this.eventForm.openUpdateModal(response.data.event, response.data.attributes);
            } else {
                alert("Erreur lors de la récupération des détails de l'événement");
            }
        };

        const row = e.target.closest('tr');
        const eventId = getEventIdFromRow(row);

        fetchAndEdit(eventId);
    }

    handleDelete(e) {
        e.stopPropagation();

        const getEventIdFromRow = row =>
            row.getAttribute('onclick').match(/\/event\/(\d+)/)[1];

        const confirmAndDelete = async eventId => {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) return;

            const result = await this.api.post(`/api/event/delete/${eventId}`, {});

            if (result.success) {
                window.location.reload();
            } else {
                alert('Erreur lors de la suppression: ' + (result.message || 'Erreur inconnue'));
            }
        };

        const row = e.target.closest('tr');
        const eventId = getEventIdFromRow(row);

        confirmAndDelete(eventId);
    }

    handleDuplicate(e) {
        e.stopPropagation();

        const eventId = e.target.dataset.id;
        const duplicateModal = new bootstrap.Modal(document.getElementById('duplicateModal'));

        const sendDuplicate = async mode => {
            const result = await this.api.post(`/api/event/duplicate/${eventId}?mode=${mode}`, {});
            if (result.success) {
                window.location.reload();
            } else {
                alert("Erreur : " + result.message);
            }
            duplicateModal.hide();
        };

        duplicateModal.show();
        document.querySelectorAll("input[name='duplicateChoice']").forEach(i => i.checked = false);

        document.getElementById("confirmDuplicate").onclick = () => {
            const choice = document.querySelector("input[name='duplicateChoice']:checked");
            if (!choice) {
                alert("Merci de sélectionner une option.");
                return;
            }

            const mode = choice.value === "1" ? "today" : choice.value === "2" ? "week" : "";
            sendDuplicate(mode);
        };
    }
}
