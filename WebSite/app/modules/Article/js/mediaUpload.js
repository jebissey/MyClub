import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient('/api');

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const uploadResult = document.getElementById('uploadResult');
    const fileNameEl = document.getElementById('fileName');
    const fileUrlEl = document.getElementById('fileUrl');
    const copyUrlBtn = document.getElementById('copyUrlBtn');

    if (!uploadForm) {
        console.warn('uploadForm introuvable');
        return;
    }

    /* ============================
       Upload fichier
       ============================ */

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(uploadForm);

        uploadStatus.innerHTML =
            '<div class="alert alert-info">Upload en cours…</div>';
        uploadStatus.style.display = 'block';
        uploadResult.style.display = 'none';

        try {
            const data = await api.postFormData('/api/media/upload', formData);
            if (data.success && data.data.file) {
                uploadStatus.innerHTML =
                    `<div class="alert alert-success">${data.message}</div>`;

                fileNameEl.textContent = data.data.file.name;
                fileUrlEl.value = data.data.file.url;
                uploadResult.style.display = 'block';
            } else {
                uploadStatus.innerHTML =
                    `<div class="alert alert-danger">${data.message || 'Erreur lors de l’upload'}</div>`;
            }

        } catch (err) {
            console.error(err);
            uploadStatus.innerHTML =
                `<div class="alert alert-danger">Erreur : ${err.message}</div>`;
        }
    });

    /* ============================
       Copier URL
       ============================ */

    copyUrlBtn?.addEventListener('click', () => {
        fileUrlEl.select();
        fileUrlEl.setSelectionRange(0, 99999); // mobile

        navigator.clipboard.writeText(fileUrlEl.value);

        const originalHTML = copyUrlBtn.innerHTML;

        copyUrlBtn.innerHTML = '<i class="bi bi-check"></i> Copié !';
        copyUrlBtn.classList.add('btn-success');
        copyUrlBtn.classList.remove('btn-outline-secondary');

        setTimeout(() => {
            copyUrlBtn.innerHTML = originalHTML;
            copyUrlBtn.classList.remove('btn-success');
            copyUrlBtn.classList.add('btn-outline-secondary');
        }, 2000);
    });
});
