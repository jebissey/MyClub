export default class AttributeManager {
    constructor(apiClient) {
        this.api = apiClient;
        this.selectedAttributes = [];
        this.eventTypeInput = null;
        this.attributesList = null;
        this.availableAttributesSelect = null;
    }

    init() {
        this.eventTypeInput = document.getElementById('eventTypeInput');
        this.attributesList = document.getElementById('attributesList');
        this.availableAttributesSelect = document.getElementById('availableAttributesSelect');

        if (!this.eventTypeInput) return;

        this.eventTypeInput.addEventListener('change', () => this.handleEventTypeChange());
        document.getElementById('addAttributeBtn')?.addEventListener('click', () => this.addAttribute());
    }

    handleEventTypeChange() {
        const eventTypeId = this.eventTypeInput.value;
        if (eventTypeId) {
            this.loadAttributesByEventType(eventTypeId);
            this.reset();
        } else {
            this.availableAttributesSelect.innerHTML = '<option value="">Sélectionnez d\'abord un type d\'événement</option>';
        }
    }

    async loadAttributesByEventType(eventTypeId) {
        this.availableAttributesSelect.innerHTML = '<option value="">Chargement...</option>';
        const data = await this.api.get(`/api/attributes/eventType/${eventTypeId}`);

        this.availableAttributesSelect.innerHTML = '';
        if (data.attributes && data.attributes.length > 0) {
            data.attributes.forEach(attr => {
                const option = document.createElement('option');
                option.value = attr.Id;
                option.textContent = attr.Name;
                option.dataset.color = attr.Color;
                option.dataset.detail = attr.Detail;
                this.availableAttributesSelect.appendChild(option);
            });
        } else {
            this.availableAttributesSelect.innerHTML = '<option value="">Aucun attribut disponible</option>';
        }
    }

    addAttribute() {
        const select = this.availableAttributesSelect;
        const selectedOption = select.options[select.selectedIndex];

        if (!selectedOption || !selectedOption.value) return;

        const attributeId = String(selectedOption.value);
        const attributeName = selectedOption.text;
        const attributeColor = selectedOption.dataset.color;
        const attributeDetail = selectedOption.dataset.detail;

        if (this.selectedAttributes.some(attr => String(attr.id) === attributeId)) {
            alert('Cet attribut a déjà été ajouté.');
            return;
        }

        const element = this.createAttributeElement(attributeId, attributeName, attributeColor, attributeDetail);
        this.attributesList.appendChild(element);
        this.selectedAttributes.push({ id: attributeId, name: attributeName, color: attributeColor, detail: attributeDetail });
    }

    createAttributeElement(id, name, color, detail) {
        const element = document.createElement('span');
        element.className = 'badge me-2 mb-2 position-relative';
        element.style.backgroundColor = color;
        element.style.color = this.getContrastYIQ(color);
        element.style.paddingRight = '25px';
        element.setAttribute('title', detail);

        element.innerHTML = `
            ${name}
            <button type="button" class="btn-close position-absolute top-0 end-0" 
                    aria-label="Supprimer" 
                    data-attribute-id="${id}"></button>
        `;

        element.querySelector('.btn-close').addEventListener('click', () => {
            this.removeAttribute(id, element);
        });

        return element;
    }

    removeAttribute(id, element) {
        this.selectedAttributes = this.selectedAttributes.filter(attr => String(attr.id) !== String(id));
        element.remove();
    }

    async loadForEvent(eventTypeId, attributes) {
        await this.loadAttributesByEventType(eventTypeId);
        this.attributesList.innerHTML = '';
        this.selectedAttributes = [];

        if (attributes) {
            attributes.forEach(attr => {
                const element = this.createAttributeElement(attr.AttributeId, attr.Name, attr.Color, attr.Detail);
                this.attributesList.appendChild(element);
                this.selectedAttributes.push({
                    id: String(attr.AttributeId),
                    name: attr.Name,
                    color: attr.Color,
                    detail: attr.Detail
                });
            });
        }
    }

    reset() {
        this.selectedAttributes = [];
        this.attributesList.innerHTML = '';
    }

    getSelectedIds() {
        return this.selectedAttributes.map(attr => attr.id);
    }

    getContrastYIQ(hexcolor) {
        hexcolor = hexcolor.replace("#", "");
        const r = parseInt(hexcolor.substr(0, 2), 16);
        const g = parseInt(hexcolor.substr(2, 2), 16);
        const b = parseInt(hexcolor.substr(4, 2), 16);
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return (yiq >= 128) ? 'black' : 'white';
    }
}
