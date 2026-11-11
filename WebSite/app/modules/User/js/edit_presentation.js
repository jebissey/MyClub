/**
 * @module edit_presentation
 * Initializes TinyMCE, the Leaflet map and the interactions of the
 * edit_presentation.latte view.
 *
 * Globals provided before this module is loaded:
 *   - tinymce (window.tinymce) and Leaflet (window.L), loaded as classic scripts
 *   - window.validationMsg and mapMaxZoom, defined by the template
 */

import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

const DEFAULT_CENTER = [47.3220, 5.0415];

function initEditor() {
    initTinyMCE('#tinymce', {
        height: 400,
        imageMaxWidth: 1200,
        imageQuality: 0.85,
        onSave() {
            document.getElementById('presentationForm').submit();
        },
    });
}

// ---------------------------------------------------------------------------
// Private location map (Leaflet)
// ---------------------------------------------------------------------------
function initMap() {
    const map = L.map('map').setView(DEFAULT_CENTER, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: mapMaxZoom
    }).addTo(map);

    let marker = null;
    const locationInput = document.getElementById('location');

    // The stored value is "lat,lng"
    const [lat, lng] = locationInput.value.split(',').map(parseFloat);
    if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
        marker = L.marker([lat, lng]).addTo(map);
        map.setView([lat, lng], 14);
    }

    map.on('click', (e) => {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        locationInput.value = `${e.latlng.lat},${e.latlng.lng}`;
    });
}

// ---------------------------------------------------------------------------
// Public location
// ---------------------------------------------------------------------------
function initPublicLocation() {
    const showLocationCheckbox = document.getElementById('showLocationInDirectory');
    const publicLocationZone   = document.getElementById('publicLocationZone');
    const publicLocationInput  = document.getElementById('myPublicDataInPresentationDirectory');

    showLocationCheckbox.addEventListener('change', function () {
        if (this.checked) {
            publicLocationZone.classList.remove('d-none');
            publicLocationInput.focus();
        } else {
            publicLocationZone.classList.add('d-none');
            publicLocationInput.value = '';
        }
    });

    return { publicLocationZone, publicLocationInput };
}

// ---------------------------------------------------------------------------
// Member directory options
// ---------------------------------------------------------------------------
function initDirectoryOptions({ publicLocationZone, publicLocationInput }) {
    const inDirectoryCheckbox = document.getElementById('inPresentationDirectory');
    const directoryOptions    = document.getElementById('directoryOptions');

    inDirectoryCheckbox.addEventListener('change', function () {
        if (this.checked) {
            directoryOptions.classList.remove('d-none');
            return;
        }

        directoryOptions.classList.add('d-none');
        document.getElementById('showPhoneInPresentationDirectory').checked = false;
        document.getElementById('showEmailInPresentationDirectory').checked = false;
        document.getElementById('showLocationInDirectory').checked = false;
        publicLocationZone.classList.add('d-none');
        publicLocationInput.value = '';
    });
}

// ---------------------------------------------------------------------------
// Form validation
// ---------------------------------------------------------------------------
function initFormValidation() {
    document.getElementById('presentationForm').addEventListener('submit', function (e) {
        const content  = tinymce.get('tinymce').getContent().trim();
        const checkbox = document.getElementById('inPresentationDirectory');

        if (checkbox.checked && (content === '' || content === '<p><br></p>')) {
            e.preventDefault();
            alert(window.validationMsg);
        }
    });
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initEditor();
    initMap();
    const publicLocation = initPublicLocation();
    initDirectoryOptions(publicLocation);
    initFormValidation();
});