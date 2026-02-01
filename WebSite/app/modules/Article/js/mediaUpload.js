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

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(uploadForm);

        uploadStatus.innerHTML = '<div class="alert alert-info">Upload en cours…</div>';
        uploadStatus.style.display = 'block';
        uploadResult.style.display = 'none';

        try {
            const data = await api.postFormData('/api/media/upload', formData);

            // Normalisation : toujours un tableau files
            let files = [];
            if (data.success) {
                if (data.data.files?.length) {
                    files = data.data.files;
                } else if (data.data.file) {
                    files = [data.data.file];
                }
            }

            if (files.length) {
                fileNameEl.textContent = files.map(f => f.name).join(', ');
                fileUrlEl.value = files.map(f => f.url).join(', ');
                uploadStatus.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                uploadResult.style.display = 'block';
            } else {
                uploadStatus.innerHTML = `<div class="alert alert-danger">${data.message || 'Erreur lors de l’upload'}</div>`;
            }

        } catch (err) {
            console.error(err);
            uploadStatus.innerHTML = `<div class="alert alert-danger">Erreur : ${err.message}</div>`;
        }
    });


    copyUrlBtn?.addEventListener('click', () => {
        if (!fileUrlEl.value) return;

        navigator.clipboard.writeText(fileUrlEl.value)
            .then(() => {
                const originalHTML = copyUrlBtn.innerHTML;
                copyUrlBtn.innerHTML = '<i class="bi bi-check"></i> Copié !';
                copyUrlBtn.classList.add('btn-success');
                copyUrlBtn.classList.remove('btn-outline-secondary');

                setTimeout(() => {
                    copyUrlBtn.innerHTML = originalHTML;
                    copyUrlBtn.classList.remove('btn-success');
                    copyUrlBtn.classList.add('btn-outline-secondary');
                }, 2000);
            })
            .catch(err => console.error('Erreur copie URL:', err));
    });
});
