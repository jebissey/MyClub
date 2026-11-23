import AttributeManager from "./AttributeManager.js";
import NeedManager from "./NeedManager.js";
import { t } from "/app/modules/Common/js/i18n.js";

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
        document.getElementById('eventModalLabel').textContent = t('events.form.create_title');
        document.getElementById('submitEventBtn').textContent = t('events.form.create');
        this.formMode.value = 'create';

        const eventTypeId = document.getElementById('eventTypeInput')?.value;
        if (eventTypeId) {
            this.attributeManager._loadAttributesByEventType(eventTypeId);
        }

        this.attributeManager.reset();
        this.needManager.reset();

        document.getElementById('maxParticipantsInput').value = 0;
        document.getElementById('audienceInput').value = 'ClubMembersOnly';

        new bootstrap.Modal(this.modal).show();
    }

    async openUpdateModal(event, attributes) {
        document.getElementById('eventModalLabel').textContent = t('events.form.update_title');
        document.getElementById('submitEventBtn').textContent = t('events.form.update');
        this.formMode.value = 'update';
        this.eventId.value = event.id;

        this.populateFormFields(event);
        await this.attributeManager.loadForEvent(event.idEventType, attributes);
        await this.needManager.loadForEvent(event.id);

        new bootstrap.Modal(this.modal).show();
    }

    populateFormFields(event) {
        document.getElementById('summaryInput').value = event.summary ?? '';
        document.getElementById('descriptionInput').value = event.description ?? '';
        document.getElementById('locationInput').value = event.location ?? '';
        document.getElementById('eventTypeInput').value = event.idEventType;
        document.getElementById('maxParticipantsInput').value = event.maxParticipants || 0;
        document.getElementById('audienceInput').value = event.audience || 'ClubMembersOnly';

        // startTime comes as local ISO 8601 without timezone ("2026-11-29T06:15:00").
        // Build the string in local time to avoid the one-day shift that
        // toISOString() (which converts to UTC) would cause.
        const startDate = new Date(event.startTime);
        document.getElementById('dateInput').value = this._formatLocalDate(startDate);
        document.getElementById('startTimeInput').value = this._formatLocalTime(startDate);

        document.getElementById('durationInput').value = (event.duration / 3600).toFixed(1);
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
                maxParticipants: parseInt(document.getElementById('maxParticipantsInput').value, 10),
                audience: document.getElementById('audienceInput').value,
                needs: this.needManager.getSelectedNeeds(),
                startTime: `${dateValue}T${timeValue}:00`,
                duration: parseInt(document.getElementById('durationInput').value, 10) * 3600
            };
        };

        const formData = buildFormData();
        const result = await this.api.post('/api/event/save', formData);

        if (result.success) {
            bootstrap.Modal.getInstance(this.modal).hide();
            window.location.reload();
        } else {
            // The server may return a localized message in result.message;
            // if not, fall back to a generic localized "unknown error".
            const detail = result.message || t('events.js.unknown_error');
            alert(`${t('events.js.error')}: ${detail}`);
        }
    }

    /**
     * Formats a Date as YYYY-MM-DD in local time (compatible with <input type="date">).
     * We avoid toISOString() because it converts to UTC and may shift the date by one day.
     * @param {Date} date
     * @returns {string}
     */
    _formatLocalDate(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) {
            return '';
        }
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    /**
     * Formats a Date as HH:MM in local time (compatible with <input type="time">).
     * @param {Date} date
     * @returns {string}
     */
    _formatLocalTime(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) {
            return '00:00';
        }
        const h = String(date.getHours()).padStart(2, '0');
        const m = String(date.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }
}
