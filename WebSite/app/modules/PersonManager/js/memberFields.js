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
        const message = window.t?.('delete_confirm')
            ?? window.i18n?.delete_confirm
            ?? 'Êtes-vous sûr de vouloir supprimer ce champ ? Toutes les valeurs déjà saisies dans ce champ seront définitivement supprimées.';
        if (!confirm(message)) return;
    }

    row.remove();
});