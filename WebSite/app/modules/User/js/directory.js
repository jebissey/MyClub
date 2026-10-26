import { watchAvatarFallbacks } from '/app/modules/Common/js/avatar.js';

watchAvatarFallbacks();

// Filters that cannot be active at the same time
const EXCLUSIVE_FILTERS = { self: 'auth', auth: 'self' };

const filterCheckboxes = document.querySelectorAll('input[data-filter]');
const loadingIndicator = document.getElementById('directoryLoading');

function setLoading(isLoading) {
    filterCheckboxes.forEach((checkbox) => {
        checkbox.disabled = isLoading;
    });
    loadingIndicator?.classList.toggle('d-none', !isLoading);
    loadingIndicator?.classList.toggle('d-flex', isLoading);
    document.body.style.cursor = isLoading ? 'progress' : '';
}

function applyFilter(checkbox) {
    const name = checkbox.dataset.filter;
    const url = new URL(window.location.href);

    if (checkbox.checked) {
        url.searchParams.set(name, '1');
        // Checking one filter removes its exclusive counterpart
        const other = EXCLUSIVE_FILTERS[name];
        if (other) {
            url.searchParams.delete(other);
            const otherCheckbox = document.querySelector(`input[data-filter="${other}"]`);
            if (otherCheckbox) {
                otherCheckbox.checked = false;
            }
        }
    } else {
        url.searchParams.delete(name);
    }

    // Show the feedback first, then navigate (the "group" parameter is kept)
    setLoading(true);
    window.location.href = url.toString();
}

filterCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => applyFilter(checkbox));
});

// When the user comes back with the browser's back button, the page may be restored
// from the cache with the loading state still active: reset it.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        setLoading(false);
    }
});