document.addEventListener('DOMContentLoaded', function () {
    const copyBtns = document.querySelectorAll('.copy-url-btn');

    copyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.getAttribute('data-url');

            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check"></i>';
            this.classList.add('btn-success');
            this.classList.remove('btn-outline-success');

            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-success');
            }, 1500);
        });
    });
});

const deleteBtns = document.querySelectorAll('.delete-file-btn');
deleteBtns.forEach(btn => {
    btn.addEventListener('click', function () {
        const path = this.getAttribute('data-path');
        const filename = this.getAttribute('data-filename');

        if (confirm(`Êtes-vous sûr de vouloir supprimer le fichier "${filename}" ?`)) {
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
                        alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.card-body').prepend(alertDiv);

                        this.closest('tr').remove();
                        const tbody = document.querySelector('tbody');
                        if (tbody.children.length === 0) {
                            const tableContainer = document.querySelector('.table-responsive');
                            tableContainer.innerHTML = `
                                <div class="alert alert-info">
                                    Aucun fichier pour l'année ${document.querySelector('select[name="year"]').value}.
                                </div>
                            `;
                        }
                    } else alert(`Erreur: ${data.message}`);
                })
                .catch(error => {
                    alert(`Erreur lors de la suppression : ${error.message}`);
                });
        }
    });
});