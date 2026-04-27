document.addEventListener('DOMContentLoaded', function () {
    initColumnHighlight();
    initToggleButton();
});

function initColumnHighlight() {
    const table = document.querySelector('table');
    if (!table) return;

    table.addEventListener('mouseover', function (e) {
        if (e.target.tagName !== 'TD') return;
        const index = e.target.cellIndex;
        if (index <= 0) return;
        table.querySelectorAll('tr').forEach(row => {
            row.cells[index]?.classList.add('table-active');
        });
    });

    table.addEventListener('mouseout', function (e) {
        if (e.target.tagName !== 'TD') return;
        table.querySelectorAll('.table-active').forEach(cell => {
            cell.classList.remove('table-active');
        });
    });
}

function initToggleButton() {
    const btn = document.getElementById('toggleBody');
    const tbody = document.querySelector('table tbody');
    if (!btn || !tbody) return;

    btn.addEventListener('click', function () {
        const visible = tbody.style.display !== 'none';
        tbody.style.display = visible ? 'none' : '';
        document.getElementById('toggleIcon').textContent = visible ? '▼' : '▲';
        document.getElementById('toggleLabel').textContent = visible
            ? window.t('tableShow')
            : window.t('tableHide');
    });
}