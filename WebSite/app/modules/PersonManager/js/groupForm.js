import { t } from "/app/modules/Common/js/i18n.js";

document.addEventListener('DOMContentLoaded', () => {
    initGroupForm();
});

function initGroupForm() {
    const nameInput = document.getElementById('name');
    const selfRegistration = document.getElementById('selfRegistration');
    const authorizations = document.querySelectorAll('input[name="authorizations[]"]');

    if (nameInput) {
        nameInput.addEventListener('invalid', () => {
            nameInput.setCustomValidity(t('person_manager.group.name_required'));
        });
        nameInput.addEventListener('input', () => {
            nameInput.setCustomValidity('');
        });
    }

    if (selfRegistration) {
        // A self-registration group cannot hold authorizations
        selfRegistration.addEventListener('change', () => {
            authorizations.forEach(checkbox => {
                if (selfRegistration.checked) {
                    checkbox.checked = false;
                }
                checkbox.disabled = selfRegistration.checked;
            });
        });
    }
}
