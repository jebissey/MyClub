import ApiClient from '../../../Common/js/ApiClient.js';

const ERR_GENERIC = 'Une erreur est survenue.';
const ERR_NO_DATA = 'Aucun événement pour ce créneau.';

export default class EventDetailModal {
    #api = new ApiClient();

    #modal = document.getElementById('eventDetailModal');
    #titleLabel = document.getElementById('eventDetailModalLabel');
    #spinner = document.getElementById('eventDetailSpinner');
    #list = document.getElementById('eventDetailList');

    bind() {
        this.#modal.addEventListener('hidden.bs.modal', () => this.#reset());
    }

    open(day, slot, dayLabel, range, startDate) {
        const slotLabel = (window.i18n && window.i18n[slot]) || slot;
        this.#titleLabel.textContent = `${dayLabel} — ${slotLabel}`;

        bootstrap.Modal.getOrCreateInstance(this.#modal).show();

        this.#loadEvents(day, slot, range, startDate);
    }

    // ── Private ────────────────────────────────────────────────────────

    async #loadEvents(day, slot, range, startDate) {
        this.#setLoading(true);

        const params = new URLSearchParams({
            day: String(day),
            slot,
            range,
            startDate: startDate ?? '',
        });
        const response = await this.#api.get(`/api/event-availabilities/slot-events?${params}`);

        if (response?.success === false) {
            this.#showMessage(response.error ?? ERR_GENERIC, 'text-danger');
            return;
        }

        const events = response?.data?.events ?? [];
        if (!Array.isArray(events) || events.length === 0) {
            this.#showMessage(ERR_NO_DATA, 'text-muted');
            return;
        }

        this.#render(events);
    }

    #setLoading(isLoading) {
        this.#spinner.style.display = isLoading ? '' : 'none';
        this.#list.style.display = isLoading ? 'none' : '';
        this.#list.innerHTML = '';
    }

    #showMessage(message, cssClass) {
        this.#setLoading(false);
        this.#list.innerHTML = `<li class="list-group-item ${cssClass}">${message}</li>`;
    }

    #render(events) {
        this.#setLoading(false);
        this.#list.innerHTML = events.map(ev => {
            const hours = Math.floor(ev.durationMinutes / 60);
            const minutes = ev.durationMinutes % 60;
            const durationLabel = hours > 0
                ? `${hours}h${String(minutes).padStart(2, '0')}`
                : `${minutes} min`;

            const participantLabel = ev.participantCount > 1
                ? `${ev.participantCount} participants`
                : `${ev.participantCount} participant`;

            return `
                <a href="/event/${ev.id}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <strong>${ev.date} — ${ev.startTime}</strong>
                        <span class="text-muted">${durationLabel}</span>
                    </div>
                    <div>${ev.summary}</div>
                    <div class="text-muted small">${participantLabel}</div>
                </a>
            `;
        }).join('');
    }

    #reset() {
        this.#list.innerHTML = '';
    }
}