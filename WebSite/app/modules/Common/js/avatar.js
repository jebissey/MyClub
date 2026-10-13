// Shared avatar rendering. The kind of avatar is decided by the server (AvatarInfo):
// "image" (e.g. Gravatar), "emoji" or "placeholder".

const HTML_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => HTML_ESCAPES[character]);
}

function placeholderHtml(size) {
    return `<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light"
        style="width: ${size}px; height: ${size}px;">
        <i class="bi bi-person-circle" style="font-size: ${Math.round(size * 0.75)}px; line-height: 1;"></i>
    </span>`;
}

/**
 * @param {{kind: string, value: string}} avatar
 * @param {number} size Diameter in pixels
 * @returns {string}
 */
export function avatarHtml(avatar, size) {
    switch (avatar.kind) {
        case 'image':
            return `<img src="${escapeHtml(avatar.value)}" alt="" data-avatar-fallback data-avatar-size="${size}"
                class="rounded-circle" style="width: ${size}px; height: ${size}px; object-fit: cover;">`;
        case 'emoji':
            return `<span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                style="font-size: ${Math.round(size * 0.75)}px; width: ${size}px; height: ${size}px; line-height: ${size}px; background-color: #f0f0f0;">
                ${escapeHtml(avatar.value)}
            </span>`;
        default:
            return placeholderHtml(size);
    }
}

/**
 * Replaces by the placeholder every image marked data-avatar-fallback that fails to load
 * (e.g. a member who selected Gravatar but has no Gravatar account).
 *
 * @param {ParentNode} [root=document]
 */
export function watchAvatarFallbacks(root = document) {
    root.querySelectorAll('img[data-avatar-fallback]').forEach((img) => {
        const size = Number(img.dataset.avatarSize);
        const replace = () => {
            img.outerHTML = placeholderHtml(size);
        };
        img.removeAttribute('data-avatar-fallback');
        if (img.complete && img.naturalWidth === 0) {
            replace();
            return;
        }
        img.addEventListener('error', replace, { once: true });
    });
}