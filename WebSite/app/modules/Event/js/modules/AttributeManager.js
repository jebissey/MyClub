export default class AttributeManager {
    constructor(apiClient) {
        this.api = apiClient;

        this.selectedAttributes = [];
        this.eventTypeInput = null;
        this.attributesList = null;
        this.availableAttributesSelect = null;
        this._init();
    }

    _init() {
        this.eventTypeInput = document.getElementById('eventTypeInput');
        this.attributesList = document.getElementById('attributesList');
        this.availableAttributesSelect = document.getElementById('availableAttributesSelect');

        if (!this.eventTypeInput) return;

        const handleEventTypeChange = async () => {
            const eventTypeId = this.eventTypeInput.value;
            if (!eventTypeId) {
                this.availableAttributesSelect.innerHTML =
                    `<option value="">Sélectionnez d'abord un type d'événement</option>`;
                return;
            }

            await loadAttributesByEventType(eventTypeId);
            reset();
        };

        const loadAttributesByEventType = async eventTypeId => {
            this.availableAttributesSelect.innerHTML = `<option value="">Chargement...</option>`;
            const data = await this.api.get(`/api/attributes/eventType/${eventTypeId}`);

            this.availableAttributesSelect.innerHTML = '';
            if (!data.attributes || data.attributes.length === 0) {
                this.availableAttributesSelect.innerHTML = `<option value="">Aucun attribut disponible</option>`;
                return;
            }

            data.attributes.forEach(attr => {
                const option = document.createElement('option');
                option.value = attr.Id;
                option.textContent = attr.Name;
                option.dataset.color = attr.Color;
                option.dataset.detail = attr.Detail;
                this.availableAttributesSelect.appendChild(option);
            });
        };

        const addAttribute = () => {
            const select = this.availableAttributesSelect;
            const option = select.options[select.selectedIndex];
            if (!option || !option.value) return;

            const id = String(option.value);

            if (this.selectedAttributes.some(a => a.id === id)) {
                alert("Cet attribut a déjà été ajouté.");
                return;
            }

            const element = createAttributeElement(
                id,
                option.text,
                option.dataset.color,
                option.dataset.detail
            );

            this.attributesList.appendChild(element);
            this.selectedAttributes.push({
                id,
                name: option.text,
                color: option.dataset.color,
                detail: option.dataset.detail
            });
        };

        const removeAttribute = (id, element) => {
            this.selectedAttributes = this.selectedAttributes.filter(a => a.id !== id);
            element.remove();
        };

        const createAttributeElement = (id, name, color, detail) => {
            const element = document.createElement('span');
            element.className = 'badge me-2 mb-2 position-relative';
            element.style.backgroundColor = color;
            element.style.color = getContrastYIQ(color);
            element.style.paddingRight = '25px';
            element.title = detail;

            element.innerHTML = `
                ${name}
                <button type="button"
                        class="btn-close position-absolute top-0 end-0"
                        data-attribute-id="${id}"></button>
            `;

            element.querySelector('.btn-close')
                .addEventListener('click', () => removeAttribute(id, element));

            return element;
        };

        const getContrastYIQ = hex => {
            hex = hex.replace("#", "");
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            return ((r * 299 + g * 587 + b * 114) / 1000) >= 128 ? "black" : "white";
        };

        const reset = () => {
            this.selectedAttributes = [];
            this.attributesList.innerHTML = '';
        };

        this.eventTypeInput.addEventListener('change', handleEventTypeChange);
        document.getElementById('addAttributeBtn')?.addEventListener('click', addAttribute);

        // expose reset for loadForEvent
        this._resetInternal = reset;
        this._loadAttributesByEventType = loadAttributesByEventType;
        this._createAttributeElement = createAttributeElement;
    }

    async loadForEvent(eventTypeId, attributes) {
        await this._loadAttributesByEventType(eventTypeId);

        this.attributesList.innerHTML = '';
        this.selectedAttributes = [];

        if (!attributes) return;

        attributes.forEach(attr => {
            const element = this._createAttributeElement(
                String(attr.AttributeId),
                attr.Name,
                attr.Color,
                attr.Detail
            );
            this.attributesList.appendChild(element);
            this.selectedAttributes.push({
                id: String(attr.AttributeId),
                name: attr.Name,
                color: attr.Color,
                detail: attr.Detail
            });
        });
    }

    reset() {
        this._resetInternal();
    }

    getSelectedIds() {
        return this.selectedAttributes.map(a => a.id);
    }
}
