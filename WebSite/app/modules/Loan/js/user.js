/**
 * user.js – réservations sur place (tout utilisateur connecté)
 */

const modalRes = new bootstrap.Modal(document.getElementById('modalRes'));
const alertEl  = document.getElementById('resAlert');

// ── Utilitaires ────────────────────────────────────────────────────────────

function showAlert(msg, type = 'danger') {
    alertEl.textContent = msg;
    alertEl.className   = `alert alert-${type} alert-dismissible fade show`;
    alertEl.innerHTML  += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
}

async function api(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const json = await res.json();
    if (!json.success) throw new Error(json.message ?? window.t('msg.error'));
    return json.data;
}

// ── Disponibilité ──────────────────────────────────────────────────────────

async function checkResAvailability() {
    const itemId = document.getElementById('resItem').value;
    const date   = document.getElementById('resDate').value;
    const start  = document.getElementById('resStart').value;
    const end    = document.getElementById('resEnd').value;
    const el     = document.getElementById('resItemAvail');
    if (!itemId || !date || !start || !end) { el.textContent = ''; return; }

    try {
        const excludeId = document.getElementById('resId').value || '';
        let url = `/api/loan/availability/${itemId}?type=reservation&date=${date}&start=${start}&end=${end}`;
        if (excludeId) url += `&excludeId=${excludeId}`;
        const data = await api(url);
        el.textContent = `${window.t('availability.available')} : ${data.available}`;
        el.className   = `form-text ${data.available > 0 ? 'text-success' : 'text-danger'}`;
    } catch (_) { el.textContent = ''; }
}

['resItem','resDate','resStart','resEnd'].forEach(id =>
    document.getElementById(id).addEventListener('change', checkResAvailability)
);

// ── Ouverture modal ────────────────────────────────────────────────────────

function openAdd() {
    document.getElementById('modalResLabel').textContent = window.t('reservation.add');
    document.getElementById('resId').value    = '';
    document.getElementById('resItem').value  = '';
    document.getElementById('resQty').value   = 1;
    document.getElementById('resDate').value  = new Date().toISOString().slice(0, 10);
    document.getElementById('resStart').value = '09:00';
    document.getElementById('resEnd').value   = '17:00';
    document.getElementById('resNotes').value = '';
    document.getElementById('resItemAvail').textContent = '';

    if (window.isManager) {
        const sel = document.getElementById('resUser');
        if (sel) sel.value = '';
    }
    modalRes.show();
}

async function openEdit(id) {
    try {
        const res = await api(`/api/loan/reservation/${id}`);
        document.getElementById('modalResLabel').textContent = window.t('reservation.title');
        document.getElementById('resId').value    = res.Id;
        document.getElementById('resItem').value  = res.ItemId;
        document.getElementById('resQty').value   = res.QuantityReserved;
        document.getElementById('resDate').value  = res.ReservationDate;
        document.getElementById('resStart').value = res.StartTime;
        document.getElementById('resEnd').value   = res.EndTime;
        document.getElementById('resNotes').value = res.Notes;

        if (window.isManager) {
            const sel = document.getElementById('resUser');
            if (sel) sel.value = res.UserId;
        }
        await checkResAvailability();
        modalRes.show();
    } catch (e) {
        showAlert(e.message);
    }
}

// ── Sauvegarde ─────────────────────────────────────────────────────────────

document.getElementById('btnSaveRes').addEventListener('click', async () => {
    const itemId = document.getElementById('resItem').value;
    const date   = document.getElementById('resDate').value;
    const start  = document.getElementById('resStart').value;
    const end    = document.getElementById('resEnd').value;
    const qty    = document.getElementById('resQty').value;

    if (!itemId || !date || !start || !end) {
        showAlert('Tous les champs obligatoires doivent être remplis.');
        return;
    }
    if (start >= end) {
        showAlert("L'heure de fin doit être après l'heure de début.");
        return;
    }

    const body = {
        id:               document.getElementById('resId').value || 0,
        itemId, quantity: qty,
        reservationDate:  date,
        startTime:        start,
        endTime:          end,
        notes:            document.getElementById('resNotes').value,
    };

    if (window.isManager) {
        const sel = document.getElementById('resUser');
        if (sel) body.userId = sel.value;
    }

    const spinner = document.getElementById('saveResSpinner');
    spinner.classList.remove('d-none');
    try {
        await api('/api/loan/reservation/save', 'POST', body);
        modalRes.hide();
        location.reload();
    } catch (e) {
        showAlert(e.message);
    } finally {
        spinner.classList.add('d-none');
    }
});

// ── Annulation ─────────────────────────────────────────────────────────────

async function cancelReservation(id) {
    if (!confirm(window.t('reservation.cancel_confirm'))) return;
    try {
        await api(`/api/loan/reservation/cancel/${id}`, 'POST');
        showAlert(window.t('msg.cancelled'), 'success');
        location.reload();
    } catch (e) {
        showAlert(e.message);
    }
}

// ── Événements ─────────────────────────────────────────────────────────────

document.getElementById('btnAddReservation').addEventListener('click', openAdd);

document.getElementById('tblReservations').addEventListener('click', e => {
    const btnEdit   = e.target.closest('.btn-edit-res');
    if (btnEdit)   { openEdit(btnEdit.dataset.id); return; }

    const btnCancel = e.target.closest('.btn-cancel-res');
    if (btnCancel) { cancelReservation(btnCancel.dataset.id); }
});