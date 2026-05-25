import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient();
const t = (key) => window.t?.(key) ?? key;

// ── Edit image modal ──────────────────────────────────────────────────────────

const editModal = new bootstrap.Modal(document.getElementById('editImageModal'));
const editImageEl = document.getElementById('editImageEl');
const editFilename = document.getElementById('editImageFilename');
const editStatus = document.getElementById('editImageStatus');
const maxSizeInput = document.getElementById('maxSizeInput');
const resetCropBtn = document.getElementById('resetCropBtn');
const saveEditBtn = document.getElementById('saveEditBtn');
const cropDimensions = document.getElementById('cropDimensions');

let cropper = null;
let currentEditPath = null;

function setEditStatus(message, type = 'info') {
    editStatus.innerHTML = `<div class="alert alert-${type} py-2">${message}</div>`;
}
function clearEditStatus() {
    editStatus.innerHTML = '';
}

/** Resize a canvas so neither dimension exceeds maxPx. Returns same canvas if already within bounds. */
function resizeCanvas(canvas, maxPx) {
    const { width, height } = canvas;
    if (width <= maxPx && height <= maxPx) return canvas;

    const ratio = Math.min(maxPx / width, maxPx / height);
    const newW = Math.round(width * ratio);
    const newH = Math.round(height * ratio);

    const out = document.createElement('canvas');
    out.width = newW;
    out.height = newH;
    out.getContext('2d').drawImage(canvas, 0, 0, newW, newH);
    return out;
}

function updateDimensionHint() {
    if (!cropper) return;
    const d = cropper.getData(true);          // rounded integers
    const maxPx = Math.min(parseInt(maxSizeInput.value, 10) || 1200, 1200);
    const ratio = Math.min(maxPx / d.width, maxPx / d.height, 1);
    const outW = Math.round(d.width * ratio);
    const outH = Math.round(d.height * ratio);
    cropDimensions.textContent = `${d.width} × ${d.height} px → ${outW} × ${outH} px`;
}

// Clamp the max-size input to [50, 1200]
maxSizeInput.addEventListener('change', () => {
    let v = parseInt(maxSizeInput.value, 10);
    if (isNaN(v) || v < 50) v = 50;
    if (v > 1200) v = 1200;
    maxSizeInput.value = v;
    updateDimensionHint();
});

resetCropBtn.addEventListener('click', () => cropper?.reset());

// Remplacer le bloc document.querySelectorAll('.edit-image-btn') entier

let imageLoaded = false;
let modalShown = false;

function tryInitCropper() {
    if (!imageLoaded || !modalShown) return;

    if (cropper) { cropper.destroy(); cropper = null; }

    cropper = new Cropper(editImageEl, {
        viewMode: 1,
        autoCropArea: 1,
        responsive: true,
        background: false,
        crop() { updateDimensionHint(); },
    });
}

document.getElementById('editImageModal').addEventListener('shown.bs.modal', () => {
    modalShown = true;
    tryInitCropper();
});

document.getElementById('editImageModal').addEventListener('hidden.bs.modal', () => {
    modalShown = false;
    imageLoaded = false;
    if (cropper) { cropper.destroy(); cropper = null; }
});

document.querySelectorAll('.edit-image-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        currentEditPath = btn.dataset.path;
        editFilename.textContent = btn.dataset.filename;
        clearEditStatus();
        cropDimensions.textContent = '';
        maxSizeInput.value = 1200;

        imageLoaded = false;
        modalShown = false;
        if (cropper) { cropper.destroy(); cropper = null; }

        editImageEl.onload = () => {
            imageLoaded = true;
            tryInitCropper();
        };
        editImageEl.src = btn.dataset.url + '?_=' + Date.now();

        editModal.show();
    });
});

// Destroy cropper when modal closes to free memory
document.getElementById('editImageModal').addEventListener('hidden.bs.modal', () => {
    if (cropper) { cropper.destroy(); cropper = null; }
});

saveEditBtn.addEventListener('click', async () => {
    if (!cropper) return;

    const maxPx = Math.min(parseInt(maxSizeInput.value, 10) || 1200, 1200);

    // 1. Get cropped canvas at natural resolution
    let canvas = cropper.getCroppedCanvas();

    // 2. Resize if any dimension exceeds maxPx
    canvas = resizeCanvas(canvas, maxPx);

    // 3. Determine output format from filename
    const ext = currentEditPath.split('.').pop().toLowerCase();
    const mime = (ext === 'png') ? 'image/png' : 'image/jpeg';
    const quality = (mime === 'image/jpeg') ? 0.92 : undefined;

    // 4. Convert to base64 and POST
    const imageData = canvas.toDataURL(mime, quality);

    saveEditBtn.disabled = true;
    saveEditBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + t('saving');

    try {
        const response = await api.post('/api/media/editImage', {
            path: currentEditPath,
            imageData,
            maxSize: maxPx,
        });

        if (response.success === false) {
            setEditStatus(t('editError'), 'danger');
            return;
        }

        setEditStatus(t('editSaved'), 'success');

        // Refresh the thumbnail in the table row
        const row = document.querySelector(`tr .edit-image-btn[data-path="${CSS.escape(currentEditPath)}"]`)?.closest('tr');
        if (row) {
            const thumb = row.querySelector('td:first-child img');
            if (thumb) thumb.src = thumb.src.split('?')[0] + '?_=' + Date.now();
        }

        setTimeout(() => editModal.hide(), 1200);
    } finally {
        saveEditBtn.disabled = false;
        saveEditBtn.innerHTML = '<i class="bi bi-floppy me-1"></i>' + t('editSave');
    }
});