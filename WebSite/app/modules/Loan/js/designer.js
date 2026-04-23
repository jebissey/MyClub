/**
 * designer.js – gestion du catalogue matériel (LoanDesigner)
 */

const modal   = new bootstrap.Modal(document.getElementById('modalItem'));
const alertEl = document.getElementById('loanAlert');

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

// ── Ouverture modal ────────────────────────────────────────────────────────

function openAdd() {
    document.getElementById('modalItemLabel').textContent = window.t('item.add');
    document.getElementById('itemId').value          = '';
    document.getElementById('itemName').value        = '';
    document.getElementById('itemDescription').value = '';
    document.getElementById('itemType').value        = 'both';
    document.getElementById('itemQuantity').value    = 1;
    document.getElementById('itemActive').checked    = true;
    modal.show();
}

async function openEdit(id) {
    try {
        const item = await api(`/api/loan/item/${id}`);
        document.getElementById('modalItemLabel').textContent = window.t('item.edit');
        document.getElementById('itemId').value          = item.Id;
        document.getElementById('itemName').value        = item.Name;
        document.getElementById('itemDescription').value = item.Description;
        document.getElementById('itemType').value        = item.Type;
        document.getElementById('itemQuantity').value    = item.Quantity;
        document.getElementById('itemActive').checked    = item.IsActive == 1;
        modal.show();
    } catch (e) {
        showAlert(e.message);
    }
}

// ── Sauvegarde ─────────────────────────────────────────────────────────────

document.getElementById('btnSaveItem').addEventListener('click', async () => {
    const name = document.getElementById('itemName').value.trim();
    if (!name) { showAlert('Le nom est obligatoire.'); return; }

    const spinner = document.getElementById('saveItemSpinner');
    spinner.classList.remove('d-none');

    try {
        await api('/api/loan/item/save', 'POST', {
            id:          document.getElementById('itemId').value || 0,
            name,
            description: document.getElementById('itemDescription').value,
            type:        document.getElementById('itemType').value,
            quantity:    document.getElementById('itemQuantity').value,
            isActive:    document.getElementById('itemActive').checked ? 1 : 0,
        });
        modal.hide();
        location.reload();
    } catch (e) {
        showAlert(e.message);
    } finally {
        spinner.classList.add('d-none');
    }
});

// ── Suppression ────────────────────────────────────────────────────────────

async function deleteItem(id, name) {
    if (!confirm(`${window.t('item.delete_confirm')}\n« ${name} »`)) return;
    try {
        await api(`/api/loan/item/delete/${id}`, 'POST');
        document.querySelector(`tr[data-id="${id}"]`)?.remove();
        showAlert(window.t('msg.deleted'), 'success');
    } catch (e) {
        showAlert(e.message);
    }
}

// ── Événements globaux ─────────────────────────────────────────────────────

document.getElementById('btnAddItem').addEventListener('click', openAdd);

document.getElementById('tblItems').addEventListener('click', e => {
    const btnEdit = e.target.closest('.btn-edit-item');
    if (btnEdit) { openEdit(btnEdit.dataset.id); return; }

    const btnDel = e.target.closest('.btn-delete-item');
    if (btnDel) { deleteItem(btnDel.dataset.id, btnDel.dataset.name); }
});