const carouselItemsManager = {
    init: function () {
        if (document.getElementById('edit-toggle-btn') && typeof ARTICLE_ID !== 'undefined') {
            this.setupCarouselManager();
        }
    },

    setupCarouselManager: function () {
        const editorContainer = document.getElementById('editor-container');
        if (!editorContainer) return;

        const carouselSection = document.createElement('div');
        carouselSection.className = 'mt-4';
        carouselSection.innerHTML = `
            <hr>
            <h4>Gestion de la galerie</h4>
            <div id="carousel-items-container" class="mb-3">
                <div id="carousel-items-list"></div>
                <button type="button" id="add-carousel-item" class="btn btn-outline-primary mt-2">
                    <i class="bi bi-plus-circle"></i> Ajouter un élément
                </button>
            </div>
        `;

        const contentInput = document.querySelector('#editor-container .mb-3');
        contentInput.parentNode.insertBefore(carouselSection, contentInput.nextSibling);

        document.getElementById('add-carousel-item').addEventListener('click', this.addNewCarouselItem.bind(this));

        this.loadCarouselItems();
    },

    loadCarouselItems: function () {
        fetch(`/api/carousel/${ARTICLE_ID}`)
            .then(response => response.json())
            .then(data => {
                const itemsList = document.getElementById('carousel-items-list');
                itemsList.innerHTML = '';

                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        this.renderCarouselItem(item);
                    });
                } else {
                    itemsList.innerHTML = '<p class="text-muted">Aucun élément dans la galerie</p>';
                }
            })
            .catch(error => {
                console.error('Error loading carousel items:', error);
            });
    },

    renderCarouselItem: function (item) {
        const itemsList = document.getElementById('carousel-items-list');
        const itemElement = document.createElement('div');
        itemElement.className = 'card mb-2';
        itemElement.dataset.itemId = item.Id;

        const isImage = item.Item.toLowerCase().startsWith('<img');
        const previewContent = isImage ?
            `<div class="d-flex justify-content-center align-items-center p-2">${item.Item}</div>` :
            `<div class="p-2">${item.Item}</div>`;

        itemElement.innerHTML = `
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        ${previewContent}
                    </div>
                    <div class="col-md-4 d-flex flex-column justify-content-center">
                        <div class="btn-group">
                            <button type="button" class="edit-carousel-item btn btn-sm btn-primary">Modifier</button>
                            <button type="button" class="delete-carousel-item btn btn-sm btn-danger">Supprimer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        itemElement.querySelector('.edit-carousel-item').addEventListener('click', () => {
            this.editCarouselItem(item);
        });

        itemElement.querySelector('.delete-carousel-item').addEventListener('click', () => {
            this.deleteCarouselItem(item.Id);
        });

        itemsList.appendChild(itemElement);
    },

    addNewCarouselItem: function () {
        this.showCarouselItemModal({}, true);
    },

    editCarouselItem: function (item) {
        this.showCarouselItemModal(item, false);
    },

    showCarouselItemModal: function (item, isNew) {
        let modal = document.getElementById('carouselItemModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'carouselItemModal';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('aria-labelledby', 'carouselItemModalLabel');
            modal.setAttribute('aria-hidden', 'true');

            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="carouselItemModalLabel">Élément de la galerie</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="carousel-item-form">
                                <input type="hidden" id="carousel-item-id">
                                <div class="mb-3">
                                    <label for="carousel-item-type" class="form-label">Type de contenu</label>
                                    <select class="form-select" id="carousel-item-type">
                                        <option value="image">Image</option>
                                        <option value="html">Contenu HTML</option>
                                    </select>
                                </div>
                                <div id="image-input-container" class="mb-3">
                                    <label for="carousel-item-image-url" class="form-label">URL de l'image</label>
                                    <input type="text" class="form-control" id="carousel-item-image-url" placeholder="https://example.com/image.jpg">
                                    <div class="form-text">Entrez l'URL d'une image publique</div>
                                </div>
                                <div id="html-input-container" class="mb-3" style="display: none;">
                                    <label for="carousel-item-html" class="form-label">Contenu HTML</label>
                                    <textarea class="form-control" id="carousel-item-html" rows="5"></textarea>
                                    <div class="form-text">Entrez du code HTML (texte, images, etc.)</div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="carousel-item-preview">
                                        <label class="form-check-label" for="carousel-item-preview">
                                            Aperçu en direct
                                        </label>
                                    </div>
                                </div>
                                <div id="carousel-item-preview-container" class="border p-3 mb-3" style="display: none;">
                                    <div id="carousel-item-preview-content"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" id="save-carousel-item" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('carousel-item-type').addEventListener('change', function () {
                const type = this.value;
                if (type === 'image') {
                    document.getElementById('image-input-container').style.display = 'block';
                    document.getElementById('html-input-container').style.display = 'none';
                } else {
                    document.getElementById('image-input-container').style.display = 'none';
                    document.getElementById('html-input-container').style.display = 'block';
                }
            });

            document.getElementById('carousel-item-preview').addEventListener('change', function () {
                document.getElementById('carousel-item-preview-container').style.display = this.checked ? 'block' : 'none';
                if (this.checked) {
                    carouselItemsManager.updatePreview();
                }
            });

            document.getElementById('carousel-item-image-url').addEventListener('input', this.debounce(this.updatePreview.bind(this), 500));
            document.getElementById('carousel-item-html').addEventListener('input', this.debounce(this.updatePreview.bind(this), 500));

            document.getElementById('save-carousel-item').addEventListener('click', this.saveCarouselItem.bind(this));
        }

        const modalTitle = document.getElementById('carouselItemModalLabel');
        modalTitle.textContent = isNew ? 'Ajouter un élément à la galerie' : 'Modifier un élément de la galerie';

        document.getElementById('carousel-item-id').value = item.Id || '';
        document.getElementById('carousel-item-preview').checked = false;
        document.getElementById('carousel-item-preview-container').style.display = 'none';

        if (!isNew && item.Item) {
            if (item.Item.toLowerCase().startsWith('<img')) {
                document.getElementById('carousel-item-type').value = 'image';
                document.getElementById('image-input-container').style.display = 'block';
                document.getElementById('html-input-container').style.display = 'none';

                const match = item.Item.match(/src="([^"]+)"/);
                document.getElementById('carousel-item-image-url').value = match ? match[1] : '';
            } else {
                document.getElementById('carousel-item-type').value = 'html';
                document.getElementById('image-input-container').style.display = 'none';
                document.getElementById('html-input-container').style.display = 'block';
                document.getElementById('carousel-item-html').value = item.Item;
            }
        } else {
            document.getElementById('carousel-item-type').value = 'image';
            document.getElementById('image-input-container').style.display = 'block';
            document.getElementById('html-input-container').style.display = 'none';
            document.getElementById('carousel-item-image-url').value = '';
            document.getElementById('carousel-item-html').value = '';
        }

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    },

    updatePreview: function () {
        if (!document.getElementById('carousel-item-preview').checked) return;

        const previewContainer = document.getElementById('carousel-item-preview-content');
        const type = document.getElementById('carousel-item-type').value;

        if (type === 'image') {
            const imageUrl = document.getElementById('carousel-item-image-url').value.trim();
            if (imageUrl) {
                previewContainer.innerHTML = `<img src="${imageUrl}" class="img-fluid" alt="Preview">`;
            } else {
                previewContainer.innerHTML = '<div class="alert alert-info">Entrez une URL d\'image pour l\'aperçu</div>';
            }
        } else {
            const htmlContent = document.getElementById('carousel-item-html').value.trim();
            if (htmlContent) {
                previewContainer.innerHTML = htmlContent;
            } else {
                previewContainer.innerHTML = '<div class="alert alert-info">Entrez du contenu HTML pour l\'aperçu</div>';
            }
        }
    },

    saveCarouselItem: function () {
        const id = document.getElementById('carousel-item-id').value;
        const type = document.getElementById('carousel-item-type').value;
        let content = '';

        if (type === 'image') {
            const imageUrl = document.getElementById('carousel-item-image-url').value.trim();
            if (!imageUrl) {
                alert('Veuillez entrer une URL d\'image valide');
                return;
            }
            content = `<img src="${imageUrl}" class="img-fluid" alt="Image">`;
        } else {
            content = document.getElementById('carousel-item-html').value.trim();
            if (!content) {
                alert('Veuillez entrer du contenu HTML');
                return;
            }
        }

        const data = {
            id: id || null,
            idArticle: ARTICLE_ID,
            item: content
        };

        fetch('/api/carousel/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('carouselItemModal'));
                    modal.hide();

                    this.loadCarouselItems();
                } else {
                    alert('Erreur: ' + (result.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Error saving carousel item:', error);
                alert('Erreur lors de l\'enregistrement de l\'élément');
            });
    },

    deleteCarouselItem: function (itemId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            return;
        }

        fetch(`/api/carousel/delete/${itemId}`, {
            method: 'POST'
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.loadCarouselItems();
                } else {
                    alert('Erreur: ' + (result.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Error deleting carousel item:', error);
                alert('Erreur lors de la suppression de l\'élément');
            });
    },

    debounce: function (func, wait) {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
};

document.addEventListener('DOMContentLoaded', function () {
    carouselItemsManager.init();
});
