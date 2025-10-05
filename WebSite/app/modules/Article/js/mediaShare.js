document.addEventListener('DOMContentLoaded', function () {
    let currentFilePath = '';

    const shareModalElement = document.getElementById('shareModal');
    const createShareBtn = document.getElementById('createShareBtn');
    const deleteShareBtn = document.getElementById('deleteShareBtn');
    const copyShareLink = document.getElementById('copyShareLink');
    const shareFileName = document.getElementById('shareFileName');
    const groupSelect = document.getElementById('group-select');
    const membersOnlyCheckbox = document.getElementById('members-only-checkbox');
    const shareStatus = document.getElementById('shareStatus');
    const shareLink = document.getElementById('shareLink');
    const shareLinkInput = document.getElementById('shareLinkInput');

    if (!shareModalElement) {
        console.error('Modal shareModal introuvable');
        return;
    }

    const shareModal = new bootstrap.Modal(shareModalElement);
    let currentFileRow = null;

    shareModalElement.addEventListener('hidden.bs.modal', function () {
        if (currentFileRow && currentFilePath) {
            updateFileRow(currentFilePath, currentFileRow);
        }
    });

    document.querySelectorAll('.share-file-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentFilePath = this.dataset.path;
            currentFileRow = this.closest('tr');
            const fileName = this.dataset.filename;
            if (shareFileName) {
                shareFileName.textContent = fileName;
            }
            loadShareInfo(currentFilePath);
            shareModal.show();
        });
    });

    if (createShareBtn) {
        createShareBtn.addEventListener('click', function () {
            const idGroup = groupSelect ? groupSelect.value : '';
            const membersOnly = membersOnlyCheckbox ? (membersOnlyCheckbox.checked ? 1 : 0) : 0;
            const path = currentFilePath;
            const pathParts = path.split('/');
            const year = pathParts[0];
            const month = pathParts[1];
            const file = pathParts[2];

            fetch(`/api/media/shareFile/${year}/${month}/${file}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idGroup: idGroup,
                    membersOnly: membersOnly
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showShareLink(data.token);
                        createShareBtn.classList.add('d-none');
                        if (deleteShareBtn) {
                            deleteShareBtn.classList.remove('d-none');
                        }
                    } else {
                        alert('Erreur lors de la création du partage: ' + (data.error || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la création du partage');
                });
        });
    }

    if (deleteShareBtn) {
        deleteShareBtn.addEventListener('click', function () {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce partage ?')) return;

            const path = currentFilePath;
            const pathParts = path.split('/');
            const year = pathParts[0];
            const month = pathParts[1];
            const file = pathParts[2];

            fetch(`/api/media/removeShare/${year}/${month}/${file}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (shareLink) {
                            shareLink.classList.add('d-none');
                        }
                        if (createShareBtn) {
                            createShareBtn.classList.remove('d-none');
                        }
                        deleteShareBtn.classList.add('d-none');
                        if (shareStatus) {
                            shareStatus.innerHTML = '<div class="alert alert-info">Aucun partage actif</div>';
                        }
                    } else {
                        alert('Erreur lors de la suppression du partage: ' + (data.error || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    alert('Erreur lors de la suppression du partage: ' + error);
                });
        });
    }

    if (copyShareLink) {
        copyShareLink.addEventListener('click', function () {
            if (!shareLinkInput) return;

            shareLinkInput.select();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareLinkInput.value).then(() => {
                    const btn = this;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check"></i> Copié !';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                    }, 2000);
                });
            } else {
                // Fallback for old browsers
                document.execCommand('copy');
                const btn = this;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> Copié !';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                }, 2000);
            }
        });
    }

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
                    if (groupSelect) {
                        groupSelect.value = data.data.IdGroup || '';
                    }
                    if (membersOnlyCheckbox) {
                        membersOnlyCheckbox.checked = data.data.MembersOnly == 1;
                    }
                    showShareLink(data.data.Token);
                    if (createShareBtn) {
                        createShareBtn.classList.add('d-none');
                    }
                    if (deleteShareBtn) {
                        deleteShareBtn.classList.remove('d-none');
                    }
                    if (shareStatus) {
                        shareStatus.innerHTML = '<div class="alert alert-success">Partage actif</div>';
                    }
                } else {
                    if (groupSelect) {
                        groupSelect.value = '';
                    }
                    if (membersOnlyCheckbox) {
                        membersOnlyCheckbox.checked = true;
                    }
                    if (shareLink) {
                        shareLink.classList.add('d-none');
                    }
                    if (createShareBtn) {
                        createShareBtn.classList.remove('d-none');
                    }
                    if (deleteShareBtn) {
                        deleteShareBtn.classList.add('d-none');
                    }
                    const editShareBtn = document.getElementById('editShareBtn');
                    if (editShareBtn) {
                        editShareBtn.classList.add('d-none');
                    }
                    if (shareStatus) {
                        shareStatus.innerHTML = '<div class="alert alert-info">Aucun partage actif</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                if (shareStatus) {
                    shareStatus.innerHTML = '<div class="alert alert-warning">Erreur lors du chargement des informations</div>';
                }
            });
    }

    function showShareLink(token) {
        const baseUrl = window.location.origin;
        const shareUrl = `${baseUrl}/media/sharedFile/${token}`;
        if (shareLinkInput) {
            shareLinkInput.value = shareUrl;
        }
        if (shareLink) {
            shareLink.classList.remove('d-none');
        }
    }

    function updateFileRow(filePath, row) {
        fetch('/api/media/isShared', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ item: filePath })
        })
            .then(response => response.json())
            .then(data => {
                const sharedCell = row.querySelector('td:nth-child(7)');
                if (sharedCell) {
                    if (data.success && data.data) {
                        sharedCell.textContent = 'Oui';
                    } else {
                        sharedCell.textContent = '';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour de la ligne:', error);
            });
    }

    document.querySelectorAll('.copy-url-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(() => {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-clipboard');
                    icon.classList.add('bi-check');
                    setTimeout(() => {
                        icon.classList.remove('bi-check');
                        icon.classList.add('bi-clipboard');
                    }, 2000);
                }
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
                        const cardBody = document.querySelector('.card-body');
                        if (cardBody) {
                            cardBody.prepend(alertDiv);
                        }
                        this.closest('tr').remove();
                        const tbody = document.querySelector('tbody');
                        if (tbody && tbody.children.length === 0) {
                            const tableContainer = document.querySelector('.table-responsive');
                            const yearSelect = document.querySelector('select[name="year"]');
                            const currentYear = yearSelect ? yearSelect.value : '';
                            if (tableContainer) {
                                tableContainer.innerHTML = `<div class="alert alert-info">
                                    Aucun fichier pour l'année ${currentYear}.
                                </div>`;
                            }
                        }
                    } else {
                        alert(`Erreur: ${data.message}`);
                    }
                })
                .catch(error => {
                    alert(`Erreur lors de la suppression : ${error.message}`);
                });
        });
    });
});