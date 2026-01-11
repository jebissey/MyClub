export default class NeedManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.selectedNeeds = [];
        this.needTypeInput = null;
        this.needsList = null;
        this.availableNeedsSelect = null;
    }

    init() {
        this.needTypeInput = document.getElementById('needTypeInput');
        this.needsList = document.getElementById('needsList');
        this.availableNeedsSelect = document.getElementById('availableNeedsSelect');

        if (!this.needTypeInput) return;

        this.needTypeInput.addEventListener('change', () => this.handleNeedTypeChange());
        document.getElementById('addNeedBtn')?.addEventListener('click', () => this.addNeed());
    }

    handleNeedTypeChange() {
        const needTypeId = this.needTypeInput.value;
        if (needTypeId) {
            this.loadNeedsByNeedType(needTypeId);
        } else {
            this.availableNeedsSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type de besoin</option>';
        }
    }

    async loadNeedsByNeedType(needTypeId) {
        this.availableNeedsSelect.innerHTML = '<option value="">Chargement...</option>';
        const data = await this.api.get(`/api/needs-by-need-type/${needTypeId}`);

        this.availableNeedsSelect.innerHTML = '';
        if (data.success && data.needs && data.needs.length > 0) {
            data.needs.forEach(need => {
                const option = document.createElement('option');
                option.value = need.Id;
                option.textContent = need.Name;
                option.dataset.needLabel = need.Label;
                option.dataset.needParticipantDependent = need.ParticipantDependent;
                this.availableNeedsSelect.appendChild(option);
            });
        } else {
            this.availableNeedsSelect.innerHTML = '<option value="">Aucun besoin disponible</option>';
        }
    }

    addNeed() {
        const select = this.availableNeedsSelect;
        const selectedOption = select.options[select.selectedIndex];

        if (!selectedOption || !selectedOption.value) return;

        const needId = selectedOption.value;
        const needName = selectedOption.text;
        const needLabel = selectedOption.dataset.needLabel;
        const participantDependent = selectedOption.dataset.needParticipantDependent;

        if (this.selectedNeeds.some(need => need.id === needId)) {
            alert('Ce besoin a déjà été ajouté.');
            return;
        }

        const element = this.createNeedElement(needId, needLabel, needName, participantDependent, 1);
        this.needsList.appendChild(element);
        this.selectedNeeds.push({
            id: needId,
            name: needName,
            label: needLabel,
            participantDependent: participantDependent,
            counter: 1
        });
    }

    createNeedElement(id, label, name, participantDependent, counter = 0) {
        const element = document.createElement('div');
        element.className = 'border rounded p-2 mb-2';
        element.setAttribute('title', name);

        let quantityHtml = '';
        if (participantDependent == 0) {
            quantityHtml = `
                <div class="input-group input-group-sm mt-1" style="width: 70px;">
                    <input type="number" class="form-control need-counter" value="${counter}" data-need-id="${id}" maxlength="3">
                </div>
            `;
        }

        element.innerHTML = `
            <div class="d-flex flex-row align-items-center bg-light rounded p-2">
                <strong class="me-2">${label}</strong>
                ${quantityHtml}
                <button type="button" class="btn btn-sm btn-danger remove-need ms-2" data-need-id="${id}">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;

        element.querySelector('.remove-need').onclick = () => this.removeNeed(id, element);
        element.querySelector('.need-counter')?.addEventListener('change', (e) => this.updateCounter(id, e.target.value));

        return element;
    }

    removeNeed(id, element) {
        this.selectedNeeds = this.selectedNeeds.filter(need => need.id !== id);
        element.remove();
    }

    updateCounter(id, value) {
        const needIndex = this.selectedNeeds.findIndex(need => need.id === id);
        if (needIndex !== -1) {
            this.selectedNeeds[needIndex].counter = parseInt(value);
        }
    }

    async loadForEvent(eventId) {
        const data = await this.api.get(`/api/event/needs/${eventId}`);

        this.needsList.innerHTML = '';
        this.selectedNeeds = [];

        if (data.success && data.needs) {
            data.needs.forEach(need => {
                const element = this.createNeedElement(need.IdNeed, need.Label, need.Name, need.ParticipantDependent, need.Counter);
                this.needsList.appendChild(element);
                this.selectedNeeds.push({
                    id: need.IdNeed,
                    name: need.Name,
                    label: need.Label,
                    participantDependent: need.ParticipantDependent,
                    counter: need.Counter
                });
            });
        }
    }

    reset() {
        this.selectedNeeds = [];
        this.needsList.innerHTML = '';
    }

    getSelectedNeeds() {
        return this.selectedNeeds.map(need => ({
            id: need.id,
            counter: need.counter
        }));
    }
}
