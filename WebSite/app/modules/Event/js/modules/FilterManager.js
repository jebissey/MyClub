export default class FilterManager {
    constructor() {
        this.filterByPreferences = document.getElementById('filterByPreferences');
        this.registeredOnly = document.getElementById('registeredOnly');
        this.eventTypeFilter = document.getElementById('eventTypeFilter');

        this.filterByPreferences?.addEventListener('change', () => this.applyFilters());
        this.registeredOnly?.addEventListener('change', () => this.applyFilters());
        this.eventTypeFilter?.addEventListener('change', () => this.applyFilters());
    }

    /**
     * Rebuilds the URL with all active filters and reloads the page.
     */
    applyFilters() {
        const currentUrl = new URL(window.location.href);

        this.#setOrDelete(currentUrl, 'filterByPreferences', this.filterByPreferences?.checked);
        this.#setOrDelete(currentUrl, 'registeredOnly', this.registeredOnly?.checked);

        const eventTypeId = this.eventTypeFilter?.value;
        if (eventTypeId) {
            currentUrl.searchParams.set('eventTypeId', eventTypeId);
        } else {
            currentUrl.searchParams.delete('eventTypeId');
        }

        // Any filter change resets pagination
        currentUrl.searchParams.set('offset', '0');

        window.location.href = currentUrl.toString();
    }

    /**
     * Sets the parameter to '1' when the value is truthy, otherwise removes it.
     *
     * @param {URL} url
     * @param {string} key
     * @param {boolean|undefined} value
     */
    #setOrDelete(url, key, value) {
        if (value) {
            url.searchParams.set(key, '1');
        } else {
            url.searchParams.delete(key);
        }
    }
}