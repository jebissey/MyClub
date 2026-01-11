import EventFormManager from './EventFormManager.js';

export default class EventTableManager {
    constructor(apiClient) {
        this.api = apiClient;
    }

    init() {
        this.setupEditButtons();
        this.setupDeleteButtons();
        this.setupDuplicateButtons();
    }

    setupEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', (e) => this.handleEdit(e));
        });
    }

    setupDeleteButtons() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', (e) => this.handleDelete(e));
        });
    }

    setupDuplicateButtons() {
        document.querySelectorAll('.duplicate-btn').forEach(button => {
            button.addEventListener('click', (e) => this.handleDuplicate(e));
        });
    }

    handleEdit(e) {
        e.stopPropagation();
        const row = e.target.closest('tr');
        const eventId = row.getAttribute('onclick').match(/\/event\/(\d+)/)[1];
        this.fetchAndEdit(eventId);
    }

    async fetchAndEdit(eventId) {
        const data = await this.api.get(`/api/event/${eventId}`);
        
        if (data.success && data.event && data.attributes) {
            const eventForm = new EventFormManager(this.api);
            eventForm.init();
            await eventForm.openUpdateModal(data.event, data.attributes);
        } else {
            alert('Erreur lors de la récupération des détails de l\'événement');
        }
    }

    handleDelete(e) {
        e.stopPropagation();
        const row = e.target.closest('tr');
        const eventId = row.getAttribute('onclick').match(/\/event\/(\d+)/)[1];
        this.confirmAndDelete(eventId);
    }

    async confirmAndDelete(eventId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) return;

        const result = await this.api.post(`/api/event/delete/${eventId}`, {});
        
        if (result.success) {
            window.location.reload();
        } else {
            alert('Erreur lors de la suppression: ' + (result.message || 'Erreur inconnue'));
        }
    }

    handleDuplicate(e) {
        e.stopPropagation();
        const eventId = e.target.dataset.id;
        const duplicateModal = new bootstrap.Modal(document.getElementById('duplicateModal'));
        
        duplicateModal.show();
        document.querySelectorAll("input[name='duplicateChoice']").forEach(input => input.checked = false);
        
        document.getElementById("confirmDuplicate").onclick = async () => {
            const choice = document.querySelector("input[name='duplicateChoice']:checked");
            if (!choice) {
                alert("Merci de sélectionner une option.");
                return;
            }

            const mode = choice.value === "1" ? "today" : choice.value === "2" ? "week" : "";
            const result = await this.api.post(`/api/event/duplicate/${eventId}?mode=${mode}`, {});
            
            if (result.success) {
                window.location.reload();
            } else {
                alert("Erreur : " + result.message);
            }
            duplicateModal.hide();
        };
    }
}
