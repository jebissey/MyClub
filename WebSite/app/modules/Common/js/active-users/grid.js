import { renderAvatar } from './avatar.js';

const TIMELINE_COLS = [12, 6, 4, 3, 2, 1];
const MAX_AVATARS_SLOT = 3;
const MAX_OVERLAP = 3;


export function pickColumnCount(userCount) {
    for (const cols of TIMELINE_COLS) {
        if (userCount / cols <= MAX_AVATARS_SLOT) return cols;
    }
    return 1;
}

export function buildActiveUsersGrid(users, windowMinutes) {
    const colCount = pickColumnCount(users.length);
    const bsCol = 12 / colCount;
    const slotWidth = windowMinutes / colCount;

    const slots = Array.from({ length: colCount }, () => []);
    for (const user of users) {
        const idx = Math.min(colCount - 1, Math.floor(user.minutesAgo / slotWidth));
        slots[idx].push(user);
    }

    const slotLabel = (i) => {
        const from = Math.round(i * slotWidth);
        const to = Math.round((i + 1) * slotWidth);
        return i === 0 ? `< ${to} min` : `${from}–${to} min`;
    };

    const columns = slots.map((slotUsers, i) => {
        const visible = slotUsers.slice(0, MAX_OVERLAP);
        const overflow = slotUsers.length - MAX_OVERLAP;

        const avatars = visible.map((u, j) => `
            <span style="position:relative;display:inline-block;
                         margin-left:${j > 0 ? '-10px' : '0'};
                         z-index:${MAX_OVERLAP - j}">
                ${renderAvatar(u)}
            </span>`
        ).join('');

        const badge = overflow > 0
            ? `<span class="badge rounded-pill bg-secondary"
                     style="font-size:.55rem;position:relative;z-index:0;
                            margin-left:2px;vertical-align:middle">
                   +${overflow}
               </span>`
            : '';

        return `
            <div class="col-${bsCol} d-flex align-items-center justify-content-center
                        py-1 border-start border-light-subtle"
                 style="min-height:44px;max-height:44px;overflow:hidden"
                 title="${slotLabel(i)}">
                ${avatars}${badge}
            </div>`;
    }).join('');

    const axis = `
        <div class="row g-0 w-100">
            <div class="col text-start text-muted" style="font-size:.6rem">maintenant</div>
            <div class="col text-end   text-muted" style="font-size:.6rem">${windowMinutes} min</div>
        </div>`;

    return `<div class="row g-0 w-100 align-items-center">${columns}</div>${axis}`;
}