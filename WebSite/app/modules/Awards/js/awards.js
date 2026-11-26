document.addEventListener('DOMContentLoaded', function () {
    const i18n = window.awardsConfig.i18n;

    const counterNameSelect = document.getElementById('counterName');
    const newNameContainer = document.getElementById('newNameContainer');
    const newCounterNameInput = document.getElementById('newCounterName');

    counterNameSelect.addEventListener('change', function () {
        if (this.value === 'new') {
            newNameContainer.classList.remove('d-none');
            newCounterNameInput.setAttribute('required', 'required');
        } else {
            newNameContainer.classList.add('d-none');
            newCounterNameInput.removeAttribute('required');
        }
    });

    const groupSelect = document.getElementById('counterGroup');
    const personSelect = document.getElementById('counterPerson');

    // Empty the person list, keep only the disabled placeholder option
    const resetPersonSelect = () => {
        personSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.selected = true;
        placeholder.disabled = true;
        placeholder.textContent = i18n.choose_person;
        personSelect.appendChild(placeholder);
        personSelect.disabled = true;
    };

    groupSelect.addEventListener('change', function () {
        const groupId = this.value;
        if (!groupId) {
            resetPersonSelect();
            return;
        }
        fetch(`/api/personsInGroup/${groupId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(i18n.network_error);
                }
                return response.json();
            })
            .then(data => {
                resetPersonSelect();
                data.forEach(person => {
                    const option = document.createElement('option');
                    option.value = person.Id;
                    option.textContent = `${person.FirstName} ${person.LastName}`;
                    personSelect.appendChild(option);
                });
                personSelect.disabled = false;
            })
            .catch(error => {
                appendAlert(`${i18n.load_persons_error} ${error.message}`, 'danger');
                personSelect.disabled = true;
            });
    });

    const submitButton = document.getElementById('submitCounter');
    const addCounterForm = document.getElementById('addCounterForm');
    submitButton.addEventListener('click', function () {
        if (counterNameSelect.value === 'new') {
            const newName = newCounterNameInput.value.trim();
            if (!newName) {
                appendAlert(i18n.name_required, 'danger');
                return;
            }
            let customNameInput = addCounterForm.querySelector('input[name="customName"]');
            if (!customNameInput) {
                customNameInput = document.createElement('input');
                customNameInput.type = 'hidden';
                customNameInput.name = 'customName';
                addCounterForm.appendChild(customNameInput);
            }
            customNameInput.value = newName;
        }

        if (!addCounterForm.checkValidity()) {
            addCounterForm.reportValidity();
            return;
        }

        addCounterForm.submit();
        bootstrap.Modal.getInstance(document.getElementById('addCounterModal')).hide();
    });

    document.getElementById('addCounterModal').addEventListener('hidden.bs.modal', function () {
        addCounterForm.reset();
        newNameContainer.classList.add('d-none');
        resetPersonSelect();
    });

    const table = document.getElementById('dataTable');
    const headers = table.querySelectorAll('th.sortable');
    let currentSortCol = -1;
    let ascending = true;
    headers.forEach((header, index) => {
        header.addEventListener('click', () => {
            headers.forEach(h => h.querySelector('.sort-icon').textContent = '');

            if (currentSortCol === index) {
                ascending = !ascending;
            } else {
                ascending = true;
                currentSortCol = index;
            }

            header.querySelector('.sort-icon').textContent = ascending ? '↑' : '↓';

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            rows.sort((a, b) => {
                let aVal = a.cells[index].textContent.trim();
                let bVal = b.cells[index].textContent.trim();

                if (a.cells[index].hasAttribute('data-value')) {
                    aVal = parseFloat(a.cells[index].getAttribute('data-value'));
                    bVal = parseFloat(b.cells[index].getAttribute('data-value'));
                }

                if (typeof aVal === 'number') {
                    return ascending ? aVal - bVal : bVal - aVal;
                }
                return ascending
                    ? aVal.localeCompare(bVal, 'fr', { sensitivity: 'base' })
                    : bVal.localeCompare(aVal, 'fr', { sensitivity: 'base' });
            });

            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        });
    });
});
