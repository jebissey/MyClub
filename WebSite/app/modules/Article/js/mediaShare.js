document.addEventListener('DOMContentLoaded', function () {
    let currentFilePath = '';
    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));

    document.querySelectorAll('.share-file-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentFilePath = this.dataset.path;
            const fileName = this.dataset.filename;
            document.getElementById('shareFileName').textContent = fileName;
            loadShareInfo(currentFilePath);
            shareModal.show();
        });
    });

    document.getElementById('createShareBtn').addEventListener('click', function () {
        const idGroup = document.getElementById('group-select').value;
        const membersOnly = document.getElementById('members-only-checkbox').checked ? 1 : 0;
        const path = currentFilePath;
        const pathParts = path.split('/');
        const year = pathParts[0];
        const month = pathParts[1];
        const file = pathParts[2];
        fetch(`/api/media/shareFile/${year}/${month}/${file}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                item: this.dataset.path,
                idGroup: idGroup || null,
                membersOnly: membersOnly
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showShareLink(data.token);
                    document.getElementById('createShareBtn').classList.add('d-none');
                    document.getElementById('deleteShareBtn').classList.remove('d-none');
                } else alert('Erreur lors de la création du partage: ' + (data.error || 'Erreur inconnue'));
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la création du partage');
            });
    });

    document.getElementById('deleteShareBtn').addEventListener('click', function () {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce partage ?')) return;

        const path = this.getAttribute('data-path');
        const pathParts = path.split('/');
        const year = pathParts[0];
        const month = pathParts[1];
        const file = pathParts[2];
        fetch(`/api/media/deleteShare/${year}/${month}/${file}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('shareLink').classList.add('d-none');
                    document.getElementById('createShareBtn').classList.remove('d-none');
                    document.getElementById('deleteShareBtn').classList.add('d-none');
                    document.getElementById('shareStatus').innerHTML = '<div class="alert alert-info">Aucun partage actif</div>';
                } else alert('Erreur lors de la suppression du partage: ' + (data.error || 'Erreur inconnue'));
            })
            .catch(error => {
                alert('Erreur lors de la suppression du partage :' + error);
            });
    });

    document.getElementById('copyShareLink').addEventListener('click', function () {
        const input = document.getElementById('shareLinkInput');
        input.select();
        document.execCommand('copy');

        const btn = this;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copié !';
        setTimeout(() => {
            btn.innerHTML = originalHTML;
        }, 2000);
    });

    function loadShareInfo(filePath) {
        fetch('/api/media/isShared', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ item: filePath })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    document.getElementById('group-select').value = data.data.IdGroup || '';
                    document.getElementById('members-only-checkbox').checked = data.data.MembersOnly == 1;
                    showShareLink(data.data.Token);
                    document.getElementById('createShareBtn').classList.add('d-none');
                    document.getElementById('deleteShareBtn').classList.remove('d-none');
                    document.getElementById('shareStatus').innerHTML = '<div class="alert alert-success">Partage actif</div>';
                } else {
                    document.getElementById('group-select').value = '';
                    document.getElementById('members-only-checkbox').checked = true;
                    document.getElementById('shareLink').classList.add('d-none');

                    document.getElementById('createShareBtn').classList.remove('d-none');
                    document.getElementById('deleteShareBtn').classList.add('d-none');
                    document.getElementById('editShareBtn')?.classList.add('d-none');

                    document.getElementById('shareStatus').innerHTML = '<div class="alert alert-info">Aucun partage actif</div>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('shareStatus').innerHTML = '<div class="alert alert-warning">Erreur lors du chargement des informations</div>';
            });
    }

    function showShareLink(token) {
        const baseUrl = window.location.origin;
        const shareUrl = `${baseUrl}/media/sharedFile/${token}`;
        document.getElementById('shareLinkInput').value = shareUrl;
        document.getElementById('shareLink').classList.remove('d-none');
    }

    document.querySelectorAll('.copy-url-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(() => {
                const icon = this.querySelector('i');
                icon.classList.remove('bi-clipboard');
                icon.classList.add('bi-check');
                setTimeout(() => {
                    icon.classList.remove('bi-check');
                    icon.classList.add('bi-clipboard');
                }, 2000);
            });
        });
    });

    const deleteBtns = document.querySelectorAll('.delete-file-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const filename = this.getAttribute('data-filename');
            if (!confirm(`Êtes-vous sûr de vouloir supprimer le fichier "${filename}" ?`)) return;

            const path = this.getAttribute('data-path');
            const pathParts = path.split('/');
            const year = pathParts[0];
            const month = pathParts[1];
            const file = pathParts[2];
            fetch(`/api/media/delete/${year}/${month}/${file}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `${data.message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
                        document.querySelector('.card-body').prepend(alertDiv);
                        this.closest('tr').remove();
                        const tbody = document.querySelector('tbody');
                        if (tbody.children.length === 0) {
                            const tableContainer = document.querySelector('.table-responsive');
                            tableContainer.innerHTML = `<div class="alert alert-info">
                                    Aucun fichier pour l'année ${document.querySelector('select[name="year"]').value}.
                                </div>`;
                        }
                    } else alert(`Erreur: ${data.message}`);
                })
                .catch(error => {
                    alert(`Erreur lors de la suppression : ${error.message}`);
                });
        });
    });
});
