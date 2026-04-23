/**
 * manager.js – gestion des prêts à emporter (LoanManager)
 */

const modalLoan   = new bootstrap.Modal(document.getElementById('modalLoan'));
const modalReturn = new bootstrap.Modal(document.getElementById('modalReturn'));
const alertEl     = document.getElementById('loanAlert');

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

// ── Vérification disponibilité ─────────────────────────────────────────────

async function checkAvailability() {
    const itemId = document.getElementById('loanItem').value;
    const date   = document.getElementById('loanDate').value;
    const due    = document.getElementById('loanDue').value;
    const el     = document.getElementById('loanItemAvail');
    if (!itemId || !date || !due) { el.textContent = ''; return; }

    try {
        const excludeId = document.getElementById('loanId').value || '';
        let url = `/api/loan/availability/${itemId}?type=loan&date=${date}&dueDate=${due}`;
        if (excludeId) url += `&excludeId=${excludeId}`;
        const data = await api(url);
        el.textContent = `${window.t('availability.available')} : ${data.available}`;
        el.className   = `form-text ${data.available > 0 ? 'text-success' : 'text-danger'}`;
    } catch (_) { el.textContent = ''; }
}

['loanItem','loanDate','loanDue'].forEach(id =>
    document.getElementById(id).addEventListener('change', checkAvailability)
);

// ── Modal nouveau prêt ─────────────────────────────────────────────────────

function openAddLoan() {
    document.getElementById('modalLoanLabel').textContent = window.t('record.add');
    document.getElementById('loanId').value       = '';
    document.getElementById('loanItem').value     = '';
    document.getElementById('loanQty').value      = 1;
    document.getElementById('loanBorrower').value = '';
    document.getElementById('loanLender').value   = '';
    document.getElementById('loanDate').value     = new Date().toISOString().slice(0, 10);
    document.getElementById('loanDue').value      = '';
    document.getElementById('loanNotes').value    = '';
    document.getElementById('loanItemAvail').textContent = '';
    modalLoan.show();
}

async function openEditLoan(id) {
    try {
        const loan = await api(`/api/loan/record/${id}`);
        document.getElementById('modalLoanLabel').textContent = window.t('record.title');
        document.getElementById('loanId').value       = loan.Id;
        document.getElementById('loanItem').value     = loan.ItemId;
        document.getElementById('loanQty').value      = loan.QuantityLent;
        document.getElementById('loanBorrower').value = loan.BorrowerId;
        document.getElementById('loanLender').value   = loan.LenderId;
        document.getElementById('loanDate').value     = loan.LoanDate;
        document.getElementById('loanDue').value      = loan.DueDate;
        document.getElementById('loanNotes').value    = loan.Notes;
        await checkAvailability();
        modalLoan.show();
    } catch (e) {
        showAlert(e.message);
    }
}

// ── Sauvegarde prêt ────────────────────────────────────────────────────────

document.getElementById('btnSaveLoan').addEventListener('click', async () => {
    const itemId     = document.getElementById('loanItem').value;
    const borrowerId = document.getElementById('loanBorrower').value;
    const lenderId   = document.getElementById('loanLender').value;
    const loanDate   = document.getElementById('loanDate').value;
    const dueDate    = document.getElementById('loanDue').value;
    const quantity   = document.getElementById('loanQty').value;

    if (!itemId || !borrowerId || !lenderId || !loanDate || !dueDate) {
        showAlert('Tous les champs obligatoires doivent être remplis.');
        return;
    }
    if (loanDate > dueDate) {
        showAlert('La date de retour prévue doit être après la date de prêt.');
        return;
    }

    const spinner = document.getElementById('saveLoanSpinner');
    spinner.classList.remove('d-none');
    try {
        await api('/api/loan/record/save', 'POST', {
            id:         document.getElementById('loanId').value || 0,
            itemId, borrowerId, lenderId, loanDate,
            dueDate,    quantity,
            notes:      document.getElementById('loanNotes').value,
        });
        modalLoan.hide();
        location.reload();
    } catch (e) {
        showAlert(e.message);
    } finally {
        spinner.classList.add('d-none');
    }
});

// ── Modal retour ───────────────────────────────────────────────────────────

function openReturn(id) {
    document.getElementById('returnLoanId').value  = id;
    document.getElementById('returnDate').value    = new Date().toISOString().slice(0, 10);
    document.getElementById('returnedTo').value    = '';
    modalReturn.show();
}

document.getElementById('btnConfirmReturn').addEventListener('click', async () => {
    const id         = document.getElementById('returnLoanId').value;
    const returnDate = document.getElementById('returnDate').value;
    const returnedTo = document.getElementById('returnedTo').value;
    if (!returnedTo) { showAlert('Veuillez sélectionner la personne qui reçoit le retour.'); return; }

    const spinner = document.getElementById('returnSpinner');
    spinner.classList.remove('d-none');
    try {
        await api(`/api/loan/record/return/${id}`, 'POST', { returnDate, returnedToId: returnedTo });
        modalReturn.hide();
        showAlert(window.t('msg.returned'), 'success');
        location.reload();
    } catch (e) {
        showAlert(e.message);
    } finally {
        spinner.classList.add('d-none');
    }
});

// ── Annulation prêt ────────────────────────────────────────────────────────

async function cancelLoan(id) {
    if (!confirm('Annuler ce prêt ?')) return;
    try {
        await api(`/api/loan/record/cancel/${id}`, 'POST');
        showAlert(window.t('msg.cancelled'), 'success');
        location.reload();
    } catch (e) {
        showAlert(e.message);
    }
}

// ── Filtre par statut ──────────────────────────────────────────────────────

document.getElementById('filterStatus').addEventListener('change', function () {
    const filter = this.value;
    document.querySelectorAll('#tbodyLoans tr[data-status]').forEach(tr => {
        tr.style.display = (!filter || tr.dataset.status === filter) ? '' : 'none';
    });
});

// ── Événements globaux ─────────────────────────────────────────────────────

document.getElementById('btnAddLoan').addEventListener('click', openAddLoan);

document.getElementById('tblLoans').addEventListener('click', e => {
    const btnEdit   = e.target.closest('.btn-edit-loan');
    if (btnEdit)   { openEditLoan(btnEdit.dataset.id); return; }

    const btnReturn = e.target.closest('.btn-return');
    if (btnReturn) { openReturn(btnReturn.dataset.id); return; }

    const btnCancel = e.target.closest('.btn-cancel-loan');
    if (btnCancel) { cancelLoan(btnCancel.dataset.id); }
});