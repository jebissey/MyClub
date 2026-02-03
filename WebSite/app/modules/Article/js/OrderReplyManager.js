import ApiClient from '../../Common/js/ApiClient.js';

export default class OrderReplyManager {
    constructor(articleId, userEmail) {
        this.articleId = articleId;
        this.userEmail = userEmail;
        this.api = new ApiClient();
        this.modalInstance = null;
    }

    init() {
        const replyBtn = document.getElementById('reply-order-btn');
        if (replyBtn) {
            replyBtn.addEventListener('click', () => this.handleReplyClick());
        }
    }

    async handleReplyClick() {
        try {
            const response = await this.api.get(`/api/order/reply/${this.articleId}`);
            
            if (response.success) {
                this.showOrderModal(response.data.order);
            } else {
                this.showAlert('Erreur : ' + response.message, 'danger');
            }
        } catch (error) {
            this.showAlert('Erreur lors du chargement de la commande : ' + error.message, 'danger');
        }
    }

    showOrderModal(order) {
        const modalElement = document.getElementById('orderModal');
        if (!modalElement) {
            console.error('Modal element #orderModal not found');
            return;
        }

        const container = document.getElementById('order-form-container');
        if (!container) {
            console.error('Container element #order-form-container not found');
            return;
        }

        container.innerHTML = this.buildOrderForm(order);
        this.attachFormEventListeners();

        this.modalInstance = new bootstrap.Modal(modalElement);
        this.modalInstance.show();
    }

    buildOrderForm(order) {
        const previousQuantities = order.previousAnswers ?? {};
        const optionsHtml = order.options.map(option => {
            const quantity = previousQuantities[option] ?? 0;
            const optionId = `option-${this.sanitizeId(option)}`;

            return `
                <div class="d-flex align-items-center mb-2">
                    <label class="form-label me-3 mb-0 flex-grow-1" for="${optionId}">
                        ${this.escapeHtml(option)}
                    </label>
                    <input class="form-control text-center flex-shrink-0" type="number"
                        name="order_answers"
                        data-option="${this.escapeHtml(option)}"
                        value="${quantity}"
                        min="0"
                        id="${optionId}"
                        style="width: 70px;">
                </div>
            `;
        }).join('');

        const buttonText = order.previousAnswers ? 'Mettre à jour' : 'Commander';

        return `
            <form id="order-form">
                <input type="hidden" name="order_id" value="${order.id}">
                <input type="hidden" name="user_email" value="${this.escapeHtml(this.userEmail)}">
                <h4>${this.escapeHtml(order.question)}</h4>
                <div class="mb-3">
                    ${optionsHtml}
                </div>
                <div class="text-center">
                    <button type="button" id="submit-order" class="btn btn-primary">
                        ${buttonText}
                    </button>
                </div>
            </form>
        `;
    }

    attachFormEventListeners() {
        const submitBtn = document.getElementById('submit-order');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.handleSubmit());
        }
    }

    async handleSubmit() {
        const inputs = document.querySelectorAll('input[name="order_answers"]');
        const orderAnswers = {};
        let hasAtLeastOne = false;

        inputs.forEach(input => {
            const option = input.dataset.option;
            const quantity = parseInt(input.value, 10) || 0;
            if (quantity > 0) hasAtLeastOne = true;
            orderAnswers[option] = quantity;
        });

        if (!hasAtLeastOne) {
            alert('Veuillez commander au moins un article.');
            return;
        }

        const formData = {
            order_id: document.querySelector('input[name="order_id"]').value,
            order_answers: orderAnswers
        };

        try {
            const result = await this.api.post('/api/order/reply', formData);

            if (result.success) {
                this.showAlert('Votre commande a été enregistrée avec succès.', 'info');
                if (this.modalInstance) {
                    this.modalInstance.hide();
                }
            } else {
                this.showAlert('Erreur : ' + result.message, 'danger');
            }
        } catch (error) {
            this.showAlert('Erreur lors de l\'enregistrement : ' + error.message, 'danger');
        }
    }

    showAlert(message, type = 'info') {
        if (typeof appendAlert === 'function') {
            appendAlert(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
            alert(message);
        }
    }

    // Utilitaires de sécurité
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    sanitizeId(text) {
        return text.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-_]/g, '');
    }
}