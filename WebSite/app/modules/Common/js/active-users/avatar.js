/**
 * @param {{ useGravatar, userImg, displayName, timeAgo, browser, os }} user
 */
export function renderAvatar({ useGravatar, userImg, displayName, timeAgo, browser, os }) {
    const tooltip = `${displayName} — ${timeAgo}\n${browser} / ${os}`;
    const size    = 'width:36px;height:36px;cursor:default';

    if (useGravatar === 'yes' && userImg) {
        return `<img src="${userImg}"
                     class="rounded-circle border border-2 border-success"
                     style="${size};object-fit:cover"
                     title="${tooltip}"
                     alt="${displayName}">`;
    }

    if (userImg && userImg !== '🤔') {
        return `<span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="font-size:24px;${size};background:#f0f0f0"
                      title="${tooltip}">${userImg}</span>`;
    }

    return `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                 style="${size}" title="${tooltip}">
                <i class="bi bi-person-circle" style="font-size:1.8rem"></i>
            </div>`;
}