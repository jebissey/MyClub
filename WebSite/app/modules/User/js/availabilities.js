document.addEventListener('DOMContentLoaded', function () {
    function updateRowSelector(rowType) {
        const checkboxes = document.querySelectorAll('.' + rowType);
        const selector = document.getElementById(rowType + '_selector');
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        selector.checked = allChecked;
    }

    document.querySelectorAll('.row-selector').forEach(selector => {
        selector.addEventListener('change', function () {
            const rowType = this.id.split('_')[0];
            const checkboxes = document.querySelectorAll('.' + rowType);
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    });

    ['morning', 'afternoon', 'evening'].forEach(rowType => {
        document.querySelectorAll('.' + rowType).forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                updateRowSelector(rowType);
            });
        });

        updateRowSelector(rowType);
    });
});