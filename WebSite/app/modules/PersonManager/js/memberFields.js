import { t } from "/app/modules/Common/js/i18n.js";

const list = document.getElementById('fieldList');
const template = document.getElementById('fieldRowTemplate');
let nextIndex = list.querySelectorAll('.field-row').length;

document.getElementById('addField').addEventListener('click', () => {
    list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
});

list.addEventListener('click', (event) => {
    const button = event.target.closest('.remove-field');
    if (!button) return;

    const row = button.closest('.field-row');
    const keyInput = row.querySelector('input[name$="[key]"]');
    const isExistingField = keyInput && keyInput.value !== '';

    if (isExistingField) {
        if (!confirm(t('custom_fields.delete_confirm'))) return;
    }

    row.remove();
});
