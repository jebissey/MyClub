import ApiClient from '../../Common/js/ApiClient.js';

const api = new ApiClient('/api');

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const uploadResult = document.getElementById('uploadResult');
    const fileListEl = document.getElementById('fileList');

    if (!uploadForm) {
        console.warn('uploadForm introuvable');
        return;
    }

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(uploadForm);

        uploadStatus.innerHTML = `<div class="alert alert-info">${t('uploadInProgress')}</div>`;

        uploadStatus.style.display = 'block';
        uploadResult.style.display = 'none';

        try {
            const data = await api.postFormData('/api/media/upload', formData);

            let files = [];
            if (data.success) {
                if (data.data.files?.length) {
                    files = data.data.files;
                } else if (data.data.file) {
                    files = [data.data.file];
                }
            }

            if (files.length) {
                uploadStatus.innerHTML = `<div class="alert alert-success">${t('uploadSuccess')}</div>`;

                fileListEl.innerHTML = files.map(f => `
                    <tr>
                        <td>${f.name}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control file-url" value="${f.url}" readonly>
                                <button class="btn btn-outline-secondary btn-copy" type="button" data-url="${f.url}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                uploadResult.style.display = 'block';
            } else {
                uploadStatus.innerHTML = `<div class="alert alert-danger">${t('uploadError')}</div>`;
            }

        } catch (err) {
            console.error(err);
            uploadStatus.innerHTML = `<div class="alert alert-danger">${t('uploadError')}</div>`;
        }
    });

    fileListEl?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-copy');
        if (!btn) return;

        navigator.clipboard.writeText(btn.dataset.url)
            .then(() => flashButton(btn, '<i class="bi bi-check"></i>', 'btn-success', 'btn-outline-secondary'))
            .catch(err => console.error('Erreur copie URL:', err));
    });

    function flashButton(btn, html, addClass, removeClass) {
        const original = btn.innerHTML;
        btn.innerHTML = html;
        btn.classList.add(addClass);
        btn.classList.remove(removeClass);
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove(addClass);
            btn.classList.add(removeClass);
        }, 2000);
    }
});