import ApiClient from '../../../../Common/js/ApiClient.js';
import NotificationService from '../../../../Common/js/NotificationService.js';

export default class SupplyController {

    constructor() {
        this.api = new ApiClient();
        this._init();
    }

    _init() {
        document
            .querySelectorAll('.user-supply-input')
            .forEach(input => this._bindInput(input));

        document
            .querySelectorAll('.update-supply-btn')
            .forEach(btn => this._bindButton(btn));
    }

    _bindInput(input) {
        const getButton = () => {
            return document.querySelector(
                `.update-supply-btn[data-need-id="${input.dataset.needId}"]`
            );
        };

        const handleInput = () => {
            const btn = getButton();
            const original = parseInt(input.dataset.originalValue);
            const current = parseInt(input.value) || 0;

            btn.style.display = current !== original ? 'block' : 'none';
        };

        const handleKeyPress = (e) => {
            if (e.key === 'Enter') {
                getButton()?.click();
            }
        };

        input.addEventListener('input', handleInput);
        input.addEventListener('keypress', handleKeyPress);
    }

    _bindButton(button) {
        const handleClick = async () => {
            const { eventId, needId } = button.dataset;
            const input = document.querySelector(`.user-supply-input[data-need-id="${needId}"]`);

            const updateUI = (needId, updatedNeed) => {
                if (!updatedNeed) return;

                const container = document.querySelector(
                    `[data-need-id="${needId}"]`
                );
                if (!container) return;

                const providedQuantityEl = container.querySelector('.provided-quantity');
                if (providedQuantityEl) {
                    providedQuantityEl.textContent = updatedNeed.providedQuantity;
                }

                const percentage = Math.round(updatedNeed.percentage);
                const bar = container.querySelector('.progress-bar');
                const label = container.querySelector('.progress-percentage');
                if (bar && label) {
                    bar.style.width = `${percentage}%`;
                    bar.setAttribute('aria-valuenow', percentage);
                    label.textContent = `${percentage}%`;

                    bar.className = 'progress-bar progress-bar-custom';
                    bar.classList.add(
                        percentage >= 100 ? 'bg-success'
                            : percentage > 0 ? 'bg-warning'
                                : 'bg-danger'
                    );
                }

                container.className = container.className.replace(/need-\w+/g, '');
                container.classList.add(
                    percentage >= 100 ? 'need-fulfilled'
                        : percentage > 0 ? 'need-partial'
                            : 'need-missing'
                );
            };

            const supply = parseInt(input.value) || 0;
            const originalText = button.textContent;

            button.disabled = true;
            button.textContent = 'En cours...';

            try {
                const data = await this.api.post('/api/event/updateSupply', {
                    eventId: eventId,
                    needId: needId,
                    supply: supply
                });
                if (!data.success) {
                    throw new Error(data.message || 'Erreur serveur');
                }

                updateUI(needId, data.updatedNeed);
                input.dataset.originalValue = supply;
                button.style.display = 'none';

                NotificationService.show(
                    'Apport mis à jour avec succès',
                    'success'
                );

            } catch (err) {
                console.error(err);
                NotificationService.show(err.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        };

        button.addEventListener('click', handleClick);
    }
}