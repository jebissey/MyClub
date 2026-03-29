export function showToast(message, success = true) {
    const toast = document.getElementById('result-toast');
    toast.classList.remove('bg-success', 'bg-danger');
    toast.classList.add(success ? 'bg-success' : 'bg-danger');
    document.getElementById('toast-body').textContent = message;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 10000 }).show();
}

export function escHtml(str) {
    return (str ?? '').replace(/[&<>"']/g,
        c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

export function showOverlay() {
    document.getElementById('loading-overlay').classList.replace('d-none', 'd-flex');
}

export function hideOverlay() {
    document.getElementById('loading-overlay').classList.replace('d-flex', 'd-none');
}