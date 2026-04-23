/**
 * calendar.js – Calendrier FullCalendar v6 (prêts + réservations)
 * FullCalendar est chargé via CDN (script global), pas en module ES6.
 */

document.addEventListener('DOMContentLoaded', () => {
    const calEl = document.getElementById('loanCalendar');
    const modal = new bootstrap.Modal(document.getElementById('modalEventDetail'));
    const detailTitle = document.getElementById('detailTitle');
    const detailBody = document.getElementById('detailBody');

    const calendar = new FullCalendar.Calendar(calEl, {
        locale: document.documentElement.lang || 'fr',
        initialView: 'dayGridMonth',
        height: 'auto',
        firstDay: 1,          // Lundi
        nowIndicator: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth',
        },
        buttonText: {
            today: "Auj.",
            month: "Mois",
            week: "Semaine",
            list: "Liste",
        },
        events: async (info, successCb, failureCb) => {
            try {
                const start = info.startStr.slice(0, 10);
                const end = info.endStr.slice(0, 10);
                const res = await fetch(`/api/loan/calendar?start=${start}&end=${end}`);
                const json = await res.json();
                if (json.success) {
                    successCb(json.data);
                } else {
                    failureCb(json.message);
                }
            } catch (e) {
                failureCb(e.message);
            }
        },
        eventClick: (info) => {
            const props = info.event.extendedProps;
            const ev = info.event;

            detailTitle.textContent = ev.title;

            let html = '';
            if (props.type === 'loan') {
                const start = ev.startStr;
                // FullCalendar stocke end exclusif pour allDay → on recule d'un jour
                const endDate = ev.end
                    ? new Date(ev.end.getTime() - 86400000).toISOString().slice(0, 10)
                    : start;
                html = `
                    <p class="mb-1"><strong>${window.t('record.loan_date')} :</strong> ${start}</p>
                    <p class="mb-1"><strong>${window.t('record.due_date')} :</strong> ${endDate}</p>
                    <p class="mb-0"><strong>${window.t('record.status')} :</strong>
                        <span class="badge ${badgeClass(props.status)}">
                            ${window.t('record.status.' + props.status)}
                        </span>
                    </p>`;
            } else {
                const d = ev.startStr.slice(0, 10);
                const s = ev.startStr.slice(11, 16);
                const e = ev.endStr ? ev.endStr.slice(11, 16) : '';
                html = `
                    <p class="mb-1"><strong>${window.t('reservation.date')} :</strong> ${d}</p>
                    <p class="mb-1"><strong>${window.t('reservation.start')} :</strong> ${s}</p>
                    <p class="mb-0"><strong>${window.t('reservation.end')} :</strong> ${e}</p>`;
            }
            detailBody.innerHTML = html;
            modal.show();
        },
    });

    calendar.render();
});

function badgeClass(status) {
    return {
        returned: 'bg-success',
        overdue: 'bg-danger',
        cancelled: 'bg-secondary',
    }[status] ?? 'bg-primary';
}