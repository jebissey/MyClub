document.addEventListener('DOMContentLoaded', function () {
    const groupSelect = document.getElementById('group-select');
    const publishedInput = document.getElementById('published-input');
    const membersOnlyCheckbox = document.getElementById('members-only-checkbox');

    if (groupSelect && membersOnlyCheckbox && publishedInput) {
        function updateInputsState() {
            const groupSelected = groupSelect.value !== "";
            const membersOnlyChecked = membersOnlyCheckbox.checked;

            if (!groupSelected && !membersOnlyChecked) {
                publishedInput.disabled = true;
                if (!IS_EDITOR) publishedInput.checked = false;
                membersOnlyCheckbox.disabled = false;
            } else publishedInput.disabled = false;

            if (groupSelected) {
                membersOnlyCheckbox.checked = true;
                membersOnlyCheckbox.disabled = true;
            } else membersOnlyCheckbox.disabled = false;
        }

        updateInputsState();
        groupSelect.addEventListener('change', updateInputsState);
        membersOnlyCheckbox.addEventListener('change', updateInputsState);
    }
});

document.getElementById('edit-form').addEventListener('submit', function () {
    this.querySelectorAll('input[type="checkbox"][disabled]').forEach(cb => {
        cb.disabled = false;
    });
});