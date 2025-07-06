let publishModal;

document.addEventListener('DOMContentLoaded', function () {
    publishModal = new bootstrap.Modal(document.getElementById('publishModal'));
});

function showPublish(articleId) {
    fetch(`/publish/article/${articleId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('publishContent').innerHTML = html;
            publishModal.show();
            initializeModalFeatures();
        });
}

function initializeModalFeatures() {
    const spotlightCheckbox = document.getElementById('isSpotlightActive');
    const spotlightDateDiv = document.getElementById('isSpotlightedUntil');
    const spotlightedUntilInput = document.getElementById('spotlightedUntil');
    const form = document.querySelector('#publishContent form');

    if (spotlightCheckbox && spotlightDateDiv) {
        spotlightCheckbox.addEventListener('change', function () {
            spotlightDateDiv.style.display = this.checked ? 'block' : 'none';
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (spotlightCheckbox.checked && !spotlightedUntilInput.value) {
                e.preventDefault();
                alert("Il faut choisir une date de mise à la une si vous cochez l'option.");
            }
        });
    }
}