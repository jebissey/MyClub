document.addEventListener("DOMContentLoaded", function () {
    // Partie 1: Enregistrement de la résolution d'écran
    var width = screen.width;
    var height = screen.height;
    let expiration = new Date();
    expiration.setDate(expiration.getDate() + 30);
    document.cookie = "screen_resolution=" + width + "x" + height + "; path=/; expires=" + expiration.toUTCString();

    // Partie 2: Initialisation des tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Partie 3: Gestion des modifications de formulaire (uniquement si le formulaire existe)
    const form = document.querySelector('form[data-form="checkSave"]');
    if (form) {
        let formModified = false;
        const formInputs = document.querySelectorAll('.form-check-input');
        const saveIndicator = document.getElementById('saveIndicator');

        function markAsModified() {
            formModified = true;
            if (saveIndicator) saveIndicator.style.display = 'block';
        }

        function markAsSaved() {
            formModified = false;
            if (saveIndicator) saveIndicator.style.display = 'none';
        }

        formInputs.forEach(input => {
            input.addEventListener('change', markAsModified);
        });

        form.addEventListener('submit', function () {
            markAsSaved();
        });

        window.addEventListener('beforeunload', function (e) {
            if (formModified) {
                const message = 'Des modifications non enregistrées seront perdues. Voulez-vous quitter la page?';
                e.returnValue = message;
                return message;
            }
        });
    }
});

// Gestion des alertes
const alertPlaceholder = document.getElementById('liveAlertPlaceholder');
if (alertPlaceholder) {
    const appendAlert = (message, type) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `  <div>${message}</div>`,
            '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        alertPlaceholder.append(wrapper);
    };
}

// Gestion des tooltips personnalisés
document.querySelectorAll('[data-tooltip-id]').forEach(el => {
    const tooltipContent = document.getElementById(el.getAttribute('data-tooltip-id'));
    if (tooltipContent) {
        new bootstrap.Tooltip(el, {
            html: true,
            title: tooltipContent.innerHTML
        });
    }
});