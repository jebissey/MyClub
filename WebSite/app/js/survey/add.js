document.getElementById('add-option').addEventListener('click', function () {
    const container = document.getElementById('options-container');
    const optionCount = container.querySelectorAll('input[name="options[]"]').length;

    if (optionCount < 10) {
        const newOption = document.createElement('div');
        newOption.className = 'd-flex mb-2';
        newOption.innerHTML = `
            <input type="text" class="form-control me-2" name="options[]" required>
            <button type="button" class="btn btn-danger remove-option">-</button>
        `;

        container.querySelector('.mb-3').appendChild(newOption);

        if (optionCount + 1 > 2) {
            const removeButtons = document.querySelectorAll('.remove-option');
            removeButtons.forEach(button => {
                button.disabled = false;
            });
        }
    }
});

document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('remove-option')) {
        const optionsContainer = document.getElementById('options-container');
        const options = optionsContainer.querySelectorAll('input[name="options[]"]');

        if (options.length > 2) {
            e.target.parentNode.remove();

            if (options.length - 1 <= 2) {
                const removeButtons = document.querySelectorAll('.remove-option');
                removeButtons.forEach(button => {
                    button.disabled = true;
                });
            }
        }
    }
});