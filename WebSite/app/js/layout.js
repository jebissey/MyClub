document.addEventListener("DOMContentLoaded", function () {
    var width = screen.width;
    var height = screen.height;
    let expiration = new Date();
    expiration.setDate(expiration.getDate() + 30);
    document.cookie = "screen_resolution=" + width + "x" + height + "; path=/; expires=" + expiration.toUTCString();

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

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

let appendAlert;
const alertPlaceholder = document.getElementById('liveAlertPlaceholder');
if (alertPlaceholder) {
    appendAlert = (message, type) => {
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

document.querySelectorAll('[data-tooltip-id]').forEach(el => {
    const tooltipContent = document.getElementById(el.getAttribute('data-tooltip-id'));
    if (tooltipContent) {
        new bootstrap.Tooltip(el, {
            html: true,
            title: tooltipContent.innerHTML
        });
    }
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('[SW] enregistré', registration);
            })
            .catch(error => {
                console.error('[SW] échec enregistrement', error);
            });
    });
}
