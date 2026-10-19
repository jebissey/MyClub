// Same key as layout.js (initSidebar)
const STORAGE_KEY = 'myclub_sidebar_open';

function forceSidebarState() {
    const sidebar = document.getElementById('sidebar');
    const state = sidebar?.dataset.forcedState;
    if (!sidebar || !state) return;

    // Games pages ignore the stored choice, and must not leave their own
    // state behind: restore the user's global choice when leaving the page
    const stored = localStorage.getItem(STORAGE_KEY);
    window.addEventListener('pagehide', () => {
        if (stored === null) localStorage.removeItem(STORAGE_KEY);
        else localStorage.setItem(STORAGE_KEY, stored);
    });

    sidebar.classList.toggle('d-none', state === 'closed');
}

// Deferred so that it runs after layout.js's initSidebar
document.addEventListener('DOMContentLoaded', () => setTimeout(forceSidebarState, 0));