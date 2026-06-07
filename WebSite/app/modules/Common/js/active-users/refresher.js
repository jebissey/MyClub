import ApiClient from '../ApiClient.js';
import { buildActiveUsersGrid } from './grid.js';
import { WindowAdapter } from './window-adapt.js';

const REFRESH_INTERVAL_MS = 30_000;
const INITIAL_WINDOW_MINUTES = 15;

const api = new ApiClient('');
const adapter = new WindowAdapter(INITIAL_WINDOW_MINUTES);

async function refreshActiveUsers() {
    try {
        const currentMinutes = adapter.minutes;
        const json = await api.get(`/api/chat/active-users?m=${currentMinutes}`);
        if (!json.success) return;

        const container = document.getElementById('active-users-list');
        if (!container) return;

        const users = json.data.users;
        const hasNewMessages = json.data.hasNewMessages;

        adapter.update(users);

        container.innerHTML = users.length === 0
            ? '<span class="text-muted small">Aucun utilisateur actif</span>'
            : buildActiveUsersGrid(users, currentMinutes);

        if (hasNewMessages) {
            document.getElementById('new-messages-btn')?.classList.remove('d-none');
            document.dispatchEvent(new CustomEvent('chat:newMessages'));
        }

    } catch (e) {
        console.warn('active-users refresh failed', e);
    }
}

export function startActiveUsersPolling() {
    refreshActiveUsers();
    return setInterval(refreshActiveUsers, REFRESH_INTERVAL_MS);
}