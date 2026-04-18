// ─── Screen resolution cookie ─────────────────────────────────────────────────
function setScreenResolutionCookie() {
    const expiration = new Date();
    expiration.setDate(expiration.getDate() + 30);
    document.cookie =
        `screen_resolution=${screen.width}x${screen.height}; path=/; expires=${expiration.toUTCString()}`;
}

// ─── Bootstrap tooltips ───────────────────────────────────────────────────────
function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('[data-tooltip-id]').forEach(el => {
        const content = document.getElementById(el.getAttribute('data-tooltip-id'));
        if (content) {
            new bootstrap.Tooltip(el, { html: true, title: content.innerHTML });
        }
    });
}

// ─── Unsaved-changes guard ────────────────────────────────────────────────────
function initSaveGuard() {
    const form = document.querySelector('form[data-form="checkSave"]');
    if (!form) return;

    let formModified = false;
    const saveIndicator = document.getElementById('saveIndicator');

    const markAsModified = () => {
        formModified = true;
        if (saveIndicator) saveIndicator.style.display = 'block';
    };

    const markAsSaved = () => {
        formModified = false;
        if (saveIndicator) saveIndicator.style.display = 'none';
    };

    document.querySelectorAll('.form-check-input').forEach(input => {
        input.addEventListener('change', markAsModified);
    });

    form.addEventListener('submit', markAsSaved);

    window.addEventListener('beforeunload', e => {
        if (formModified) {
            const message = 'Des modifications non enregistrées seront perdues. Voulez-vous quitter la page?';
            e.returnValue = message;
            return message;
        }
    });
}

// ─── Live alert helper ────────────────────────────────────────────────────────
function initAlertHelper() {
    const alertPlaceholder = document.getElementById('liveAlertPlaceholder');
    if (!alertPlaceholder) return;

    window.appendAlert = (message, type) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `  <div>${message}</div>`,
            '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>',
        ].join('');
        alertPlaceholder.append(wrapper);
    };
}

// ─── Sidebar toggle ───────────────────────────────────────────────────────────
function initSidebar() {
    const sidebar   = document.getElementById('sidebar-wrapper');
    const toggleBtn = document.getElementById('sidebarToggle');
    const STORAGE_KEY = 'myclub_sidebar_open';

    if (!sidebar || !toggleBtn) return;

    // Restore saved state (default: open)
    if (localStorage.getItem(STORAGE_KEY) === 'closed') {
        sidebar.classList.replace('d-lg-flex', 'd-none');
    }

    toggleBtn.addEventListener('click', () => {
        const isHidden = sidebar.classList.contains('d-none');
        if (isHidden) {
            sidebar.classList.remove('d-none');
            sidebar.classList.add('d-lg-flex');
            localStorage.setItem(STORAGE_KEY, 'open');
        } else {
            sidebar.classList.add('d-none');
            sidebar.classList.remove('d-lg-flex');
            localStorage.setItem(STORAGE_KEY, 'closed');
        }
    });

    // Rotate chevron on submenu open/close
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(el => {
        const chevron = el.querySelector('.bi-chevron-down');
        if (!chevron) return;
        const target = document.querySelector(el.getAttribute('href'));
        if (!target) return;
        target.addEventListener('show.bs.collapse', () => chevron.style.transform = 'rotate(-180deg)');
        target.addEventListener('hide.bs.collapse', () => chevron.style.transform = 'rotate(0deg)');
    });

    // Auto-expand submenu that contains the active link
    document.querySelectorAll('.collapse-submenu').forEach(submenu => {
        if (submenu.querySelector('.nav-link.active')) {
            new bootstrap.Collapse(submenu, { toggle: true });
        }
    });
}

// ─── Service Worker ───────────────────────────────────────────────────────────
function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(reg  => console.log('[SW] enregistré', reg))
            .catch(err => console.error('[SW] échec enregistrement', err));
    });
}

// ─── Entry point ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    setScreenResolutionCookie();
    initTooltips();
    initSaveGuard();
    initAlertHelper();
    initSidebar();
});

registerServiceWorker();