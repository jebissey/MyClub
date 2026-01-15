import SupplyController from '../services/supply/SupplyController.js';
import CalendarController from '../services/calendar/CalendarController.js';
import ApiClient from '../../../Common/js/ApiClient.js';

document.addEventListener('DOMContentLoaded', () => {
    new SupplyController();
    new CalendarController().init();

    initParticipantsSupplies();
});

function initParticipantsSupplies() {
    const btn = document.getElementById('btn-load-participants-supplies');
    if (!btn) return;

    const container = document.getElementById('participants-supplies-container');
    const api = new ApiClient();

    btn.addEventListener('click', async () => {
        const eventId = btn.dataset.eventId;

        btn.disabled = true;
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Chargement...';

        try {
            const html = await api.getHtml(
                `/api/participants/supplies?eventId=${encodeURIComponent(eventId)}`
            );

            container.innerHTML = html;
            container.dataset.loaded = '1';

            btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Rafraîchir';

        } catch (e) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    Impossible de charger les apports des participants.
                </div>`;
        } finally {
            btn.disabled = false;
        }
    });
}

