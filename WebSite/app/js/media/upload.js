document.addEventListener('DOMContentLoaded', function () {
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const uploadResult = document.getElementById('uploadResult');
    const fileNameEl = document.getElementById('fileName');
    const fileUrlEl = document.getElementById('fileUrl');
    const copyUrlBtn = document.getElementById('copyUrlBtn');

    uploadForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        uploadStatus.innerHTML = '<div class="alert alert-info">Upload en cours...</div>';
        uploadStatus.style.display = 'block';
        uploadResult.style.display = 'none';

        fetch('/api/media/upload', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    uploadStatus.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    fileNameEl.textContent = data.file.name;
                    fileUrlEl.value = data.file.url;
                    uploadResult.style.display = 'block';
                } else {
                    uploadStatus.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                uploadStatus.innerHTML = '<div class="alert alert-danger">Erreur: ' + error.message + '</div>';
            });
    });

    copyUrlBtn.addEventListener('click', function () {
        fileUrlEl.select();
        document.execCommand('copy');

        const originalText = copyUrlBtn.innerHTML;
        copyUrlBtn.innerHTML = '<i class="bi bi-check"></i> Copié!';
        copyUrlBtn.classList.add('btn-success');
        copyUrlBtn.classList.remove('btn-outline-secondary');

        setTimeout(function () {
            copyUrlBtn.innerHTML = originalText;
            copyUrlBtn.classList.remove('btn-success');
            copyUrlBtn.classList.add('btn-outline-secondary');
        }, 2000);
    });
});