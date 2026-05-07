/**
 * Displays Bootstrap toast notifications.
 */
export class Toast {
    /**
     * @param {string} message
     * @param {'success'|'danger'|'warning'|'info'} type
     */
    show(message, type = 'success') {
        const el = document.createElement('div');
        el.className = [
            'toast align-items-center border-0',
            'position-fixed bottom-0 end-0 m-3',
            `text-bg-${type}`,
        ].join(' ');
        el.setAttribute('role', 'alert');
        el.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        document.body.appendChild(el);
        new bootstrap.Toast(el, { delay: 3000 }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
}