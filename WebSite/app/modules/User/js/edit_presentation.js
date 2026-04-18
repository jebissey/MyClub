/**
 * @module edit_presentation
 * Initialise TinyMCE, la carte Leaflet et les interactions de la vue
 * edit_presentation.latte.
 *
 * Dépendances globales (chargées avant ce module via <script> classique) :
 *   - tinymce  (window.tinymce)
 *   - Leaflet  (window.L)
 */

import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

function initEditor() {
    initTinyMCE('#tinymce', {
        height: 400,
        imageMaxWidth: 1200,
        imageQuality: 0.85,
        onSave(editor) {
            document.getElementById('presentationForm').submit();
        },
    });
}

// ---------------------------------------------------------------------------
// Carte privée (Leaflet)
// ---------------------------------------------------------------------------
function initMap() {
    const map = L.map('map').setView([47.3220, 5.0415], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: mapMaxZoom
    }).addTo(map);

    let marker = null;
    const locationInput = document.getElementById('location');

    if (locationInput.value) {
        try {
            const [lat, lng] = locationInput.value.split(',');
            if (lat && lng) {
                marker = L.marker([parseFloat(lat), parseFloat(lng)]).addTo(map);
                map.setView([parseFloat(lat), parseFloat(lng)], 14);
            }
        } catch (e) {
            console.error('Erreur lors du parsing des coordonnées', e);
        }
    }

    map.on('click', (e) => {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        locationInput.value = `${e.latlng.lat},${e.latlng.lng}`;
    });
}

// ---------------------------------------------------------------------------
// Trombinoscope — affichage des options d'annuaire
// ---------------------------------------------------------------------------
function initDirectoryOptions() {
    const inDirectoryCheckbox = document.getElementById('inPresentationDirectory');
    const directoryOptions    = document.getElementById('directoryOptions');

    inDirectoryCheckbox.addEventListener('change', function () {
        if (this.checked) {
            directoryOptions.classList.remove('d-none');
        } else {
            directoryOptions.classList.add('d-none');
            document.getElementById('showPhoneInPresentationDirectory').checked = false;
            document.getElementById('showEmailInPresentationDirectory').checked = false;
            document.getElementById('showLocationInDirectory').checked = false;
            publicLocationZone.classList.add('d-none');   // eslint-disable-line no-use-before-define
            publicLocationInput.value = '';               // eslint-disable-line no-use-before-define
        }
    });
}

// ---------------------------------------------------------------------------
// Localisation publique
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

    // On expose ces deux références pour que initDirectoryOptions() puisse y
    // accéder via la fermeture créée ci-dessous.
    return { publicLocationZone, publicLocationInput };
}

// ---------------------------------------------------------------------------
// Validation du formulaire
// ---------------------------------------------------------------------------
function initFormValidation() {
    document.getElementById('presentationForm').addEventListener('submit', function (e) {
        const content  = tinymce.get('tinymce').getContent().trim();
        const checkbox = document.getElementById('inPresentationDirectory');

        if (checkbox.checked && (!content || content === '' || content === '<p><br></p>')) {
            e.preventDefault();
            alert(window.validationMsg);
            return false;
        }
    });
}

// ---------------------------------------------------------------------------
// Point d'entrée
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initEditor();
    initMap();
    initPublicLocation();   // doit être appelé avant initDirectoryOptions
    initDirectoryOptions();
    initFormValidation();
});