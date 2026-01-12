import AttributeManager from "./AttributeManager.js";
import NeedManager from "./NeedManager.js";

export default class EventFormManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.modal = document.getElementById('eventModal');
        this.form = document.getElementById('eventForm');
        this.attributeManager = new AttributeManager(this.api);
        this.needManager = new NeedManager(this.api);
        this.formMode = document.getElementById('formMode');
        this.eventId = document.getElementById('eventId');
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        document.querySelector('[data-bs-target="#eventModal"]')?.addEventListener('click', () => {
            this.openCreateModal();
        });
    }

    openCreateModal() {
        document.getElementById('eventModalLabel').textContent = 'Créer un événement';
        document.getElementById('submitEventBtn').textContent = 'Créer';
        this.formMode.value = 'create';

        this.attributeManager.handleEventTypeChange();

        this.needManager.reset();

        document.getElementById('maxParticipantsInput').value = 0;
        document.getElementById('audienceInput').value = 'ClubMembersOnly';

        new bootstrap.Modal(this.modal).show();
    }

    async openUpdateModal(event, attributes) {
        document.getElementById('eventModalLabel').textContent = 'Mettre à jour l\'événement';
        document.getElementById('submitEventBtn').textContent = 'Mettre à jour';
        this.formMode.value = 'update';
        this.eventId.value = event.Id;

        this.populateFormFields(event);
        await this.attributeManager.loadForEvent(event.IdEventType, attributes);
        await this.needManager.loadForEvent(event.Id);

        new bootstrap.Modal(this.modal).show();
    }

    populateFormFields(event) {
        document.getElementById('summaryInput').value = event.Summary;
        document.getElementById('descriptionInput').value = event.Description;
        document.getElementById('locationInput').value = event.Location;
        document.getElementById('eventTypeInput').value = event.IdEventType;
        document.getElementById('maxParticipantsInput').value = event.MaxParticipants || 0;
        document.getElementById('audienceInput').value = event.Audience || 'ClubMembersOnly';

        const startDate = new Date(event.StartTime);
        document.getElementById('dateInput').value = startDate.toISOString().split('T')[0];
        document.getElementById('startTimeInput').value = startDate.toTimeString().split(' ')[0].slice(0, 5);
        document.getElementById('durationInput').value = (event.Duration / 3600).toFixed(1);
    }

    async handleSubmit(e) {
        e.preventDefault();

        const buildFormData = () => {
            const dateValue = document.getElementById('dateInput').value;
            const timeValue = document.getElementById('startTimeInput').value;
            return {
                id: this.eventId.value,
                formMode: this.formMode.value,
                summary: document.getElementById('summaryInput').value,
                description: document.getElementById('descriptionInput').value,
                location: document.getElementById('locationInput').value,
                idEventType: document.getElementById('eventTypeInput').value,
                attributes: this.attributeManager.getSelectedIds(),
                maxParticipants: parseInt(document.getElementById('maxParticipantsInput').value),
                audience: document.getElementById('audienceInput').value,
                needs: this.needManager.getSelectedNeeds(),
                startTime: `${dateValue}T${timeValue}:00`,
                duration: parseInt(document.getElementById('durationInput').value * 3600)
            };
        };

        const formData = buildFormData();
        const result = await this.api.post('/api/event/save', formData);

        if (result.success) {
            bootstrap.Modal.getInstance(this.modal).hide();
            window.location.reload();
        } else {
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    }
}
