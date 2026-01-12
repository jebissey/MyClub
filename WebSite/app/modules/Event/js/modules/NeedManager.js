export default class NeedManager {
    constructor(apiClient) {
        this.api = apiClient;

        this.selectedNeeds = [];
        this.needTypeInput = null;
        this.needsList = null;
        this.availableNeedsSelect = null;
        this._init();
    }

    _init() {
        this.needTypeInput = document.getElementById('needTypeInput');
        this.needsList = document.getElementById('needsList');
        this.availableNeedsSelect = document.getElementById('availableNeedsSelect');

        if (!this.needTypeInput) return;

        const handleNeedTypeChange = async () => {
            const needTypeId = this.needTypeInput.value;
            if (!needTypeId) {
                this.availableNeedsSelect.innerHTML =
                    `<option value="">Sélectionnez d'abord un type de besoin</option>`;
                return;
            }
            await loadNeedsByNeedType(needTypeId);
        };

        const loadNeedsByNeedType = async needTypeId => {
            this.availableNeedsSelect.innerHTML = `<option value="">Chargement...</option>`;
            const data = await this.api.get(`/api/needs-by-need-type/${needTypeId}`);

            this.availableNeedsSelect.innerHTML = '';
            if (!data.success || !data.needs || data.needs.length === 0) {
                this.availableNeedsSelect.innerHTML = `<option value="">Aucun besoin disponible</option>`;
                return;
            }

            data.needs.forEach(need => {
                const option = document.createElement('option');
                option.value = need.Id;
                option.textContent = need.Name;
                option.dataset.needLabel = need.Label;
                option.dataset.needParticipantDependent = need.ParticipantDependent;
                this.availableNeedsSelect.appendChild(option);
            });
        };

        const addNeed = () => {
            const select = this.availableNeedsSelect;
            const option = select.options[select.selectedIndex];
            if (!option || !option.value) return;

            const id = String(option.value);
            if (this.selectedNeeds.some(n => n.id === id)) {
                alert("Ce besoin a déjà été ajouté.");
                return;
            }

            const element = createNeedElement(
                id,
                option.dataset.needLabel,
                option.text,
                option.dataset.needParticipantDependent,
                1
            );

            this.needsList.appendChild(element);
            this.selectedNeeds.push({
                id,
                name: option.text,
                label: option.dataset.needLabel,
                participantDependent: option.dataset.needParticipantDependent,
                counter: 1
            });
        };

        const removeNeed = (id, element) => {
            this.selectedNeeds = this.selectedNeeds.filter(n => n.id !== id);
            element.remove();
        };

        const updateCounter = (id, value) => {
            const need = this.selectedNeeds.find(n => n.id === id);
            if (need) need.counter = parseInt(value);
        };

        const createNeedElement = (id, label, name, participantDependent, counter) => {
            const element = document.createElement('div');
            element.className = 'border rounded p-2 mb-2';
            element.title = name;

            const quantityHtml = participantDependent == 0
                ? `<div class="input-group input-group-sm mt-1" style="width:70px">
                        <input type="number" class="form-control need-counter"
                               value="${counter}" data-need-id="${id}">
                   </div>`
                : '';

            element.innerHTML = `
                <div class="d-flex flex-row align-items-center bg-light rounded p-2">
                    <strong class="me-2">${label}</strong>
                    ${quantityHtml}
                    <button type="button" class="btn btn-sm btn-danger remove-need ms-2">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;

            element.querySelector('.remove-need')
                .onclick = () => removeNeed(id, element);

            element.querySelector('.need-counter')
                ?.addEventListener('change', e => updateCounter(id, e.target.value));

            return element;
        };

        const reset = () => {
            this.selectedNeeds = [];
            this.needsList.innerHTML = '';
        };

        // wiring
        this.needTypeInput.addEventListener('change', handleNeedTypeChange);
        document.getElementById('addNeedBtn')?.addEventListener('click', addNeed);

        // expose minimal internals for public API
        this._resetInternal = reset;
        this._loadNeedsByNeedType = loadNeedsByNeedType;
        this._createNeedElement = createNeedElement;
    }

    async loadForEvent(eventId) {
        const data = await this.api.get(`/api/event/needs/${eventId}`);

        this.needsList.innerHTML = '';
        this.selectedNeeds = [];

        if (!data.success || !data.needs) return;

        data.needs.forEach(need => {
            const element = this._createNeedElement(
                String(need.IdNeed),
                need.Label,
                need.Name,
                need.ParticipantDependent,
                need.Counter
            );

            this.needsList.appendChild(element);
            this.selectedNeeds.push({
                id: String(need.IdNeed),
                name: need.Name,
                label: need.Label,
                participantDependent: need.ParticipantDependent,
                counter: need.Counter
            });
        });
    }

    reset() {
        this._resetInternal();
    }

    getSelectedNeeds() {
        return this.selectedNeeds.map(n => ({
            id: n.id,
            counter: n.counter
        }));
    }
}
