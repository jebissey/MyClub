export default class DragDropManager {
    constructor(onReorder) {
        this.onReorder = onReorder;
    }

    init(list) {
        let draggedRow = null;

        list.querySelectorAll('tr').forEach(row => {
            row.draggable = true;

            row.addEventListener('dragstart', e => {
                draggedRow = row;
                row.classList.add('table-active');
                e.dataTransfer.effectAllowed = 'move';
            });

            row.addEventListener('dragend', () => {
                row.classList.remove('table-active');
                draggedRow = null;
            });

            row.addEventListener('dragover', e => e.preventDefault());

            row.addEventListener('drop', e => {
                e.preventDefault();
                if (!draggedRow || draggedRow === row) return;

                const rows = [...list.querySelectorAll('tr')];
                const insertBefore = rows.indexOf(draggedRow) < rows.indexOf(row)
                    ? row.nextSibling
                    : row;

                list.insertBefore(draggedRow, insertBefore);
                this.onReorder(list);
            });
        });
    }

    extractOrder(list) {
        const positions = {};
        const orderedIds = [];

        list.querySelectorAll('tr').forEach((row, index) => {
            const id = Number(row.dataset.id);
            positions[id] = index + 1;
            orderedIds.push(id);
        });

        return { positions, orderedIds };
    }
}