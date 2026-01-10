export default class DragDropManager {
    constructor(options = {}) {
        this.itemSelector = options.itemSelector || '.draggable-item';
        this.containerSelector = options.containerSelector || '.drop-container';
        this.draggedItemClass = options.draggedItemClass || 'dragging';
        this.dragOverClass = options.dragOverClass || 'drag-over';
        this.debug = options.debug || false;
        
        this.onDragStart = options.onDragStart || null;
        this.onDragEnd = options.onDragEnd || null;
        this.onDrop = options.onDrop || null;
        this.canDrop = options.canDrop || (() => true);
        
        this.draggedItem = null;
        
        this.handleDragStart = this.handleDragStart.bind(this);
        this.handleDragEnd = this.handleDragEnd.bind(this);
        this.handleDragOver = this.handleDragOver.bind(this);
        this.handleDrop = this.handleDrop.bind(this);
        this.handleDragEnter = this.handleDragEnter.bind(this);
        this.handleDragLeave = this.handleDragLeave.bind(this);
    }

    init(root = document) {
        this.detach(root);
        this.attach(root);
        
        if (this.debug) console.log('DragDropManager initialized');
    }

    attach(root = document) {
        const items = root.querySelectorAll(this.itemSelector);
        const containers = root.querySelectorAll(this.containerSelector);

        items.forEach(item => {
            item.draggable = true;
            item.addEventListener('dragstart', this.handleDragStart);
            item.addEventListener('dragend', this.handleDragEnd);
        });

        containers.forEach(container => {
            container.addEventListener('dragenter', this.handleDragEnter);
            container.addEventListener('dragover', this.handleDragOver);
            container.addEventListener('dragleave', this.handleDragLeave);
            container.addEventListener('drop', this.handleDrop);
        });
    }

    detach(root = document) {
        const items = root.querySelectorAll(this.itemSelector);
        const containers = root.querySelectorAll(this.containerSelector);

        items.forEach(item => {
            item.removeEventListener('dragstart', this.handleDragStart);
            item.removeEventListener('dragend', this.handleDragEnd);
        });

        containers.forEach(container => {
            container.removeEventListener('dragenter', this.handleDragEnter);
            container.removeEventListener('dragover', this.handleDragOver);
            container.removeEventListener('dragleave', this.handleDragLeave);
            container.removeEventListener('drop', this.handleDrop);
        });
    }

    handleDragStart(e) {
        this.draggedItem = e.currentTarget;
        this.draggedItem.classList.add(this.draggedItemClass);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.draggedItem.dataset.id || '');

        if (this.debug) {
            console.log('Drag start:', this.draggedItem);
        }
        if (this.onDragStart) {
            this.onDragStart(this.draggedItem, e);
        }
    }

        handleDragEnd(e) {
        setTimeout(() => {
            if (this.draggedItem) {
                this.draggedItem.classList.remove(this.draggedItemClass);
            }
            document.querySelectorAll(this.containerSelector).forEach(container => {
                container.classList.remove(this.dragOverClass);
            });

            if (this.debug) console.log('Drag end');
            if (this.onDragEnd) {
                this.onDragEnd(this.draggedItem, e);
            }
            this.draggedItem = null;
        }, 50);
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const container = e.target.closest(this.containerSelector);
        if (container && this.canDrop(this.draggedItem, container)) {
            container.classList.add(this.dragOverClass);
        }
    }

    handleDragEnter(e) {
        e.preventDefault();
        const container = e.target.closest(this.containerSelector);
        if (container && this.canDrop(this.draggedItem, container)) {
            container.classList.add(this.dragOverClass);
        }
    }

    handleDragLeave(e) {
        const container = e.target.closest(this.containerSelector);
        if (container) {
            const rect = container.getBoundingClientRect();
            if (
                e.clientX <= rect.left ||
                e.clientX >= rect.right ||
                e.clientY <= rect.top ||
                e.clientY >= rect.bottom
            ) {
                container.classList.remove(this.dragOverClass);
            }
        }
    }

    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();

        const container = e.target.closest(this.containerSelector);
        if (!container) {
            if (this.debug) console.log('Drop hors conteneur');
            return;
        }
        container.classList.remove(this.dragOverClass);
        if (!this.canDrop(this.draggedItem, container)) {
            if (this.debug) console.log('Drop non autorisé');
            return;
        }

        if (this.debug) {
            console.log('Drop:', this.draggedItem, '→', container);
        }
        if (this.onDrop) {
            const result = this.onDrop(this.draggedItem, container, e);
            
            if (result === false) {
                if (this.debug) console.log('Drop annulé par callback');
                return;
            }
        }
        container.appendChild(this.draggedItem);
    }

    destroy() {
        this.detach();
        this.draggedItem = null;
        if (this.debug) console.log('DragDropManager destroyed');
    }
}