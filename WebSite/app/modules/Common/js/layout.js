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
            const message = window.layoutI18n?.unsavedWarning ?? 'Unsaved changes will be lost. Do you want to leave the page?';
            e.returnValue = message;
            return message;
        }
    });
}

import { AlertHelper } from '/app/modules/Common/js/AlertHelper.js';
// ─── Live alert helper ────────────────────────────────────────────────────────
function initAlertHelper() {
    const helper = new AlertHelper();
    window.appendAlert = (message, type) => helper.append(message, type);
}

// ─── Sidebar toggle ───────────────────────────────────────────────────────────
function initSidebar() {
    const sidebar = document.getElementById('sidebar-wrapper');
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
            .then(reg => console.log('[SW] enregistré', reg))
            .catch(err => console.error('[SW] échec enregistrement', err));
    });
}

function showIosInstallBanner() {
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || navigator.standalone === true;
    const dismissed = sessionStorage.getItem('iosBannerDismissed');

    if (!isIos || isStandalone || dismissed) return;

    const installMessage = window.layoutI18n?.iosInstallMessage
        ?? 'Install <strong>MyClub</strong> on your iPhone: tap <strong>⎋ Share</strong> then <strong>«Add to Home Screen»</strong>';

    const banner = document.createElement('div');
    banner.id = 'ios-install-banner';
    banner.innerHTML = `
        <div style="
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999;
            background: #fff; border-top: 1px solid #ddd;
            padding: 12px 16px; display: flex;
            align-items: center; gap: 12px;
            font-family: sans-serif; font-size: 14px;
            box-shadow: 0 -2px 8px rgba(0,0,0,.15);
        ">
            <img src="/apple-touch-icon.png" width="40" height="40"
                 style="border-radius:8px; flex-shrink:0">
            <span style="flex:1">${installMessage}</span>
            <button id="ios-banner-close" style="
                background:none; border:none; font-size:20px;
                cursor:pointer; padding:4px; color:#666;
            ">✕</button>
        </div>
    `;

    document.body.appendChild(banner);

    document.getElementById('ios-banner-close').addEventListener('click', () => {
        banner.remove();
        sessionStorage.setItem('iosBannerDismissed', '1');
    });
}

document.addEventListener('DOMContentLoaded', showIosInstallBanner);

// ─── Entry point ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    setScreenResolutionCookie();
    initTooltips();
    initSaveGuard();
    initAlertHelper();
    initSidebar();
});

registerServiceWorker();