export default class FilterManager {
    init() {
        const checkbox = document.getElementById('filterByPreferences');
        if (checkbox) {
            checkbox.addEventListener('change', () => this.togglePreferencesFilter());
        }
    }

    togglePreferencesFilter() {
        const checkbox = document.getElementById('filterByPreferences');
        const currentUrl = new URL(window.location.href);

        if (checkbox.checked) {
            currentUrl.searchParams.set('filterByPreferences', '1');
        } else {
            currentUrl.searchParams.delete('filterByPreferences');
        }
        currentUrl.searchParams.set('offset', '0');
        window.location.href = currentUrl.toString();
    }
}