const t = (key) => window.t?.(key) ?? key;

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

        const path = btn.dataset.path;

        try {
            const res = await fetch('/media/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path }),
            });

            if (!res.ok) throw new Error();

            btn.closest('tr').remove();
        } catch {
            alert(t('deleteError'));
        }
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
    try {
        const res = await fetch(`/media/shareInfo?path=${encodeURIComponent(path)}`);
        if (!res.ok) throw new Error();

        const data = await res.json();

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
    } catch {
        setStatus(t('shareError'), 'danger');
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
    try {
        const res = await fetch('/media/share', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                path:        currentPath,
                idGroup:     groupSelect.value,
                membersOnly: membersOnly.checked ? 1 : 0,
            }),
        });

        if (!res.ok) throw new Error();

        const data = await res.json();
        shareLinkInput.value = data.link;
        shareLinkBox.classList.remove('d-none');
        deleteShareBtn.classList.remove('d-none');
        createShareBtn.classList.add('d-none');
        setStatus(t('shareCreated'), 'success');
    } catch {
        setStatus(t('shareError'), 'danger');
    }
});

deleteShareBtn.addEventListener('click', async () => {
    try {
        const res = await fetch('/media/shareDelete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path: currentPath }),
        });

        if (!res.ok) throw new Error();

        shareLinkBox.classList.add('d-none');
        deleteShareBtn.classList.add('d-none');
        createShareBtn.classList.remove('d-none');
        setStatus(t('shareDeleted'), 'warning');
    } catch {
        setStatus(t('shareError'), 'danger');
    }
});

copyShareLink.addEventListener('click', async () => {
    await navigator.clipboard.writeText(shareLinkInput.value);
    const original = copyShareLink.innerHTML;
    copyShareLink.innerHTML = `<i class="bi bi-check"></i> ${t('linkCopied')}`;
    setTimeout(() => { copyShareLink.innerHTML = original; }, 1500);
});