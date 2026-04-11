import { renderAvatar } from './avatar.js';

const AVATAR_SIZE = 32;
const AVATAR_OVERLAP = 10;
const MIN_COLUMN_WIDTH = 85;

function maxAvatarsForWidth(widthPx) {
    if (widthPx < MIN_COLUMN_WIDTH) {
        return 2;
    }
    const theoretical = Math.max(1, Math.floor(
        (widthPx - AVATAR_SIZE) / (AVATAR_SIZE - AVATAR_OVERLAP) + 1
    ));

    if (widthPx < 110) return Math.min(theoretical, 3);
    if (widthPx < 160) return Math.min(theoretical, 4);
    if (widthPx < 220) return Math.min(theoretical, 5);

    return Math.min(theoretical, 6);
}

function colWidthPx(colCount) {
    return Math.max(50, Math.floor((window.innerWidth - 20) / colCount));
}

export function pickColumnCount(userCount) {
    const width = window.innerWidth;

    if (width < 480) return 2;
    if (width < 768) return 3;

    const candidates = [6, 5, 4, 3, 2, 1];
    for (const cols of candidates) {
        const colW = colWidthPx(cols);
        const capacity = maxAvatarsForWidth(colW);
        if (Math.ceil(userCount / cols) <= capacity + 1) {
            return cols;
        }
    }
    return 1;
}

export function buildActiveUsersGrid(users, windowMinutes = 240) {
    const colCount = pickColumnCount(users.length);
    const colWidth = colWidthPx(colCount);
    const maxAvatarsPerSlot = maxAvatarsForWidth(colWidth);

    const bsCol = Math.floor(12 / colCount);

    const slotWidthMin = windowMinutes / colCount;

    const slots = Array.from({ length: colCount }, () => []);
    for (const user of users) {
        let idx = Math.floor(user.minutesAgo / slotWidthMin);
        idx = Math.min(colCount - 1, Math.max(0, idx));
        slots[idx].push(user);
    }

    const slotLabel = (i) => {
        const slotSize = Math.round(slotWidthMin);
        if (i === 0) return `< ${slotSize} min`;
        const from = Math.round(i * slotWidthMin);
        const to = Math.round((i + 1) * slotWidthMin);
        return `${from}–${to} min`;
    };

    const columnsHtml = slots.map((slotUsers, i) => {
        const visible = slotUsers.slice(0, maxAvatarsPerSlot);
        const overflow = slotUsers.length - maxAvatarsPerSlot;

        const avatarsHtml = visible.map((u, j) => `
            <span style="position:relative; display:inline-block;
                         margin-left: ${j > 0 ? `-${AVATAR_OVERLAP}px` : '0'};
                         z-index: ${maxAvatarsPerSlot - j};">
                ${renderAvatar(u)}
            </span>
        `).join('');

        const badge = overflow > 0 ? `
            <span class="badge rounded-pill bg-secondary ms-1" 
                  style="font-size:0.55rem; vertical-align:middle;">
                +${overflow}
            </span>` : '';

        return `
            <div class="col-${bsCol} d-flex align-items-center justify-content-center py-1 border-start border-light-subtle"
                 style="min-height:44px; max-height:44px; overflow:hidden;"
                 title="${slotLabel(i)}">
                ${avatarsHtml}${badge}
            </div>`;
    }).join('');

    const axisHtml = `
        <div class="row g-0 w-100 mt-1">
            <div class="col text-start text-muted" style="font-size:0.65rem">maintenant</div>
            <div class="col text-end text-muted" style="font-size:0.65rem">${windowMinutes} min</div>
        </div>`;

    return `
        <div class="row g-0 w-100 align-items-center">
            ${columnsHtml}
        </div>
        ${axisHtml}
    `;
}