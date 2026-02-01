import ApiClient from '../../Common/js/ApiClient.js';

export default class CarouselManager {
    constructor(articleId) {
        this.articleId = articleId;
        this.apiClient = new ApiClient();
        this.deleteModal = null;
        this.itemToDelete = null;

        this.elements = {
            alertContainer: null,
            carouselGallery: null,
            itemCount: null,
            addImageForm: null,
            imageSrc: null,
            imageAlt: null,
            deletePreview: null,
            confirmDelete: null
        };
    }

    init() {
        this.cacheElements();
        this.initModal();
        this.attachEventListeners();
    }

    cacheElements() {
        this.elements.alertContainer = document.getElementById('alertContainer');
        this.elements.carouselGallery = document.getElementById('carouselGallery');
        this.elements.itemCount = document.getElementById('itemCount');
        this.elements.addImageForm = document.getElementById('addImageForm');
        this.elements.imageSrc = document.getElementById('imageSrc');
        this.elements.imageAlt = document.getElementById('imageAlt');
        this.elements.deletePreview = document.getElementById('deletePreview');
        this.elements.confirmDelete = document.getElementById('confirmDelete');
    }

    initModal() {
        const modalElement = document.getElementById('deleteModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            this.deleteModal = new bootstrap.Modal(modalElement);
        }
    }

    attachEventListeners() {
        // Formulaire d'ajout
        this.elements.addImageForm?.addEventListener('submit', (e) => this.handleAddImage(e));

        // Boutons de suppression (délégation d'événements)
        document.addEventListener('click', (e) => this.handleDeleteClick(e));

        // Confirmation de suppression
        this.elements.confirmDelete?.addEventListener('click', () => this.handleConfirmDelete());
    }

    async handleAddImage(e) {
        e.preventDefault();

        const imageSrc = this.elements.imageSrc.value.trim();
        const imageAlt = this.elements.imageAlt.value.trim();

        if (!imageSrc) {
            this.showAlert('Veuillez saisir une URL d\'image', 'warning');
            return;
        }

        const itemHtml = `<img src="${this.escapeHtml(imageSrc)}" class="img-fluid" alt="${this.escapeHtml(imageAlt)}">`;

        const result = await this.apiClient.post('/api/carousel/save', {
            idArticle: this.articleId,
            item: itemHtml
        });

        if (result.success !== false) {
            this.showAlert(result.message || 'Image ajoutée avec succès', 'success');
            await this.loadCarouselItems();
            this.elements.addImageForm.reset();
        } else {
            this.showAlert(result.message || 'Erreur lors de l\'ajout', 'danger');
        }
    }

    async handleDeleteClick(e) {
        const deleteBtn = e.target.closest('.delete-btn');
        if (!deleteBtn) return;

        this.itemToDelete = deleteBtn.dataset.id;

        // Récupérer le HTML de l'item depuis l'attribut data
        const itemWrapper = deleteBtn.closest('.carousel-item-wrapper');
        const itemHtml = itemWrapper?.dataset.itemHtml;

        if (itemHtml) {
            const imgInfo = this.extractImgInfo(itemHtml);
            this.elements.deletePreview.src = imgInfo.src;
            this.elements.deletePreview.alt = imgInfo.alt;
        }

        this.deleteModal?.show();
    }

    async handleConfirmDelete() {
        if (!this.itemToDelete) return;

        const result = await this.apiClient.post(`/api/carousel/delete/${this.itemToDelete}`, {});

        if (result.success !== false) {
            this.showAlert(result.message || 'Image supprimée avec succès', 'success');
            this.removeItemVisually(this.itemToDelete);
            this.deleteModal?.hide();
            this.itemToDelete = null;
        } else {
            this.showAlert(result.message || 'Erreur lors de la suppression', 'danger');
        }
    }

    async loadCarouselItems() {
        const result = await this.apiClient.get(`/api/carousel/items/${this.articleId}`);

        if (result.success === false) {
            console.error('Error loading items:', result.error);
            return;
        }

        if (!result.items) return;

        const container = this.elements.carouselGallery?.parentElement ||
            document.querySelector('.card-body');

        if (!container) return;

        if (result.items.length === 0) {
            container.innerHTML = this.getEmptyStateHtml();
        } else {
            const galleryHtml = result.items.map(item => this.getItemHtml(item)).join('');
            container.innerHTML = `<div class="row g-3" id="carouselGallery">${galleryHtml}</div>`;

            // Recacher l'élément de galerie après rechargement
            this.elements.carouselGallery = document.getElementById('carouselGallery');
        }

        this.updateItemCount();
    }

    getItemHtml(item) {
        return `
            <div class="col-md-3 col-sm-4 col-6 carousel-item-wrapper" 
                 data-id="${item.Id}" 
                 data-item-html="${this.escapeHtml(item.Item)}">
                <div class="card h-100">
                    <div class="position-relative carousel-item-content">
                        ${item.Item}
                        <button type="button" 
                                class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 delete-btn" 
                                data-id="${item.Id}"
                                title="Supprimer cette image">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="card-body p-2">
                        <p class="card-text small mb-0 item-info">
                            <small class="text-muted">ID: ${item.Id}</small>
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    getEmptyStateHtml() {
        return `
            <div class="text-center py-5" id="emptyState">
                <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3">Aucune image dans le carousel</p>
                <p class="text-muted">Ajoutez votre première image avec le formulaire ci-dessus</p>
            </div>
        `;
    }

    removeItemVisually(itemId) {
        const itemElement = document.querySelector(`.carousel-item-wrapper[data-id="${itemId}"]`);
        if (!itemElement) return;

        itemElement.style.transition = 'opacity 0.3s ease';
        itemElement.style.opacity = '0';

        setTimeout(() => {
            itemElement.remove();
            this.updateItemCount();
        }, 300);
    }

    updateItemCount() {
        const count = document.querySelectorAll('.carousel-item-wrapper').length;

        if (this.elements.itemCount) {
            this.elements.itemCount.textContent = `${count} image(s)`;
        }

        if (count === 0 && this.elements.carouselGallery) {
            const container = this.elements.carouselGallery.parentElement;
            if (container) {
                container.innerHTML = this.getEmptyStateHtml();
            }
        }
    }

    showAlert(message, type = 'success') {
        if (!this.elements.alertContainer) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        this.elements.alertContainer.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    extractImgInfo(html) {
        const srcMatch = html.match(/src="([^"]+)"/);
        const altMatch = html.match(/alt="([^"]*)"/);

        return {
            src: srcMatch ? srcMatch[1] : '',
            alt: altMatch ? altMatch[1] : ''
        };
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const articleId = window.ARTICLE_ID;
    const carouselManager = new CarouselManager(articleId);
    carouselManager.init();
});