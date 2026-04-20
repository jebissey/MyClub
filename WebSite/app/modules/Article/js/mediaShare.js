import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient();
const t   = (key) => window.t?.(key) ?? key;

// ── Copy URL buttons ──────────────────────────────────────────────────────────

document.querySelectorAll('.copy-url-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const url = btn.dataset.url;
        await navigator.clipboard.writeText(url);

        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        btn.title = t('urlCopied');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.title = '';
        }, 1500);
    });
});

// ── Delete file buttons ───────────────────────────────────────────────────────

document.querySelectorAll('.delete-file-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
        if (!confirm(t('deleteConfirm'))) return;

        // path = "2024/01/fichier.ext"
        const [year, month, ...rest] = btn.dataset.path.split('/');
        const filename = rest.join('/');

        const data = await api.post(`/api/media/delete/${year}/${month}/${filename}`, {});

        if (data.success === false) {
            alert(t('deleteError'));
            return;
        }

        btn.closest('tr').remove();
    });
});

// ── Share modal ───────────────────────────────────────────────────────────────

const shareModal     = new bootstrap.Modal(document.getElementById('shareModal'));
const shareFileName  = document.getElementById('shareFileName');
const shareStatus    = document.getElementById('shareStatus');
const shareForm      = document.getElementById('shareForm');
const shareLinkBox   = document.getElementById('shareLink');
const shareLinkInput = document.getElementById('shareLinkInput');
const groupSelect    = document.getElementById('group-select');
const membersOnly    = document.getElementById('members-only-checkbox');
const createShareBtn = document.getElementById('createShareBtn');
const deleteShareBtn = document.getElementById('deleteShareBtn');
const copyShareLink  = document.getElementById('copyShareLink');

let currentPath = null;

function setStatus(message, type = 'info') {
    shareStatus.innerHTML = `<div class="alert alert-${type} py-2">${message}</div>`;
}

function clearStatus() {
    shareStatus.innerHTML = '';
}

async function loadShareState(path) {
    const data = await api.get(`/media/shareInfo?path=${encodeURIComponent(path)}`);

    if (data.success === false) {
        setStatus(t('shareError'), 'danger');
        return;
    }

    if (data.shared) {
        groupSelect.value    = data.idGroup ?? '';
        membersOnly.checked  = !!data.membersOnly;
        shareLinkInput.value = data.link ?? '';
        shareLinkBox.classList.remove('d-none');
        deleteShareBtn.classList.remove('d-none');
        createShareBtn.classList.add('d-none');
    } else {
        shareLinkBox.classList.add('d-none');
        deleteShareBtn.classList.add('d-none');
        createShareBtn.classList.remove('d-none');
    }
}

document.querySelectorAll('.share-file-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        currentPath = btn.dataset.path;
        shareFileName.textContent = btn.dataset.filename;
        clearStatus();
        loadShareState(currentPath);
        shareModal.show();
    });
});

createShareBtn.addEventListener('click', async () => {
    const data = await api.post('/media/share', {
        path:        currentPath,
        idGroup:     groupSelect.value,
        membersOnly: membersOnly.checked ? 1 : 0,
    });

    if (data.success === false) {
        setStatus(t('shareError'), 'danger');
        return;
    }

    shareLinkInput.value = data.link;
    shareLinkBox.classList.remove('d-none');
    deleteShareBtn.classList.remove('d-none');
    createShareBtn.classList.add('d-none');
    setStatus(t('shareCreated'), 'success');
});

deleteShareBtn.addEventListener('click', async () => {
    const data = await api.post('/media/shareDelete', { path: currentPath });

    if (data.success === false) {
        setStatus(t('shareError'), 'danger');
        return;
    }

    shareLinkBox.classList.add('d-none');
    deleteShareBtn.classList.add('d-none');
    createShareBtn.classList.remove('d-none');
    setStatus(t('shareDeleted'), 'warning');
});

copyShareLink.addEventListener('click', async () => {
    await navigator.clipboard.writeText(shareLinkInput.value);
    const original = copyShareLink.innerHTML;
    copyShareLink.innerHTML = `<i class="bi bi-check"></i> ${t('linkCopied')}`;
    setTimeout(() => { copyShareLink.innerHTML = original; }, 1500);
});