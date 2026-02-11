document.querySelectorAll('input[name="visibility"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const groupSelection = document.getElementById('groupSelection');
        const idGroupSelect = document.getElementById('idGroup');

        if (this.value === 'group') {
            groupSelection.style.display = 'block';
            idGroupSelect.required = true;
        } else {
            groupSelection.style.display = 'none';
            idGroupSelect.required = false;
            idGroupSelect.value = '';
        }
    });
});

document.querySelector('form').addEventListener('submit', function (e) {
    e.preventDefault();

    const visibility = document.querySelector('input[name="visibility"]:checked').value;
    const idGroup = document.getElementById('idGroup').value;
    const form = this;
    const oldHiddenInputs = form.querySelectorAll('input[name="onlyForMembers"], input[name="idGroup"]');
    oldHiddenInputs.forEach(input => input.remove());

    const onlyForMembersInput = document.createElement('input');
    onlyForMembersInput.type = 'hidden';
    onlyForMembersInput.name = 'onlyForMembers';

    let idGroupInput;
    if (visibility === 'group') {
        idGroupInput = document.createElement('input');
        idGroupInput.type = 'hidden';
        idGroupInput.name = 'idGroup';
        idGroupInput.value = idGroup;
    }

    if (visibility === 'all') {
        onlyForMembersInput.value = '0';
    } else if (visibility === 'members' || visibility === 'group') {
        onlyForMembersInput.value = '1';
    }

    form.appendChild(onlyForMembersInput);
    if (visibility === 'group') {
        form.appendChild(idGroupInput);
    }
    form.submit();
});