// Shared Leaflet map showing members, used by the members map and the event carpooling map.
// Requires the global Leaflet `L`.
// Clustering requires Leaflet.markercluster to be loaded by the page.

import { avatarHtml, watchAvatarFallbacks } from './avatar.js';

const DEFAULT_CENTER = [47.3220, 5.0415];
const DEFAULT_ZOOM = 10;

const HTML_ESCAPES = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
};

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (character) => HTML_ESCAPES[character]);
}

function createPublicPopupContent(member) {
    // myPublicData is rich text edited by the member: it is intentionally not escaped
    const publicData = member.myPublicData
        ? `<div class="mt-2 text-start" style="max-width: 220px;">${member.myPublicData}</div>`
        : '';

    return `<div class="text-center mb-2">
        <strong>${escapeHtml(member.name)}</strong>
        ${publicData}
    </div>`;
}

function createPrivatePopupContent(member) {
    let content = `<div class="text-center mb-2">
        <strong>${escapeHtml(member.name)}</strong>`;

    if (member.nickname) {
        content += `<br><span class="text-muted">${escapeHtml(member.nickname)}</span>`;
    }

    content += `<div class="mt-2">${avatarHtml(member.avatar, 64)}</div>`;

    return content + '</div>';
}

function createPopupContent(member, isPublic) {
    return isPublic ? createPublicPopupContent(member) : createPrivatePopupContent(member);
}

function createMarkerLayer(cluster) {
    if (!cluster) {
        return L.layerGroup();
    }

    return L.markerClusterGroup({
        iconCreateFunction(clusterGroup) {
            const count = clusterGroup.getChildCount();
            return L.divIcon({
                html: `<div style="font-size:36px; font-weight:bold;">${count}</div>`,
                className: 'custom-cluster',
                iconSize: L.point(50, 50),
            });
        },
    });
}

/**
 * Creates a map showing the given members and fits the view to them.
 *
 * @param {Object} options
 * @param {string} options.elementId Id of the container element
 * @param {Array<Object>} options.members Markers as serialized by MemberMapMarker
 * @param {number} options.maxZoom Maximum zoom level
 * @param {boolean} [options.isPublic=false] Public popups show the public data instead of the avatar
 * @param {boolean} [options.cluster=false] Groups nearby markers (needs Leaflet.markercluster)
 * @returns {L.Map}
 */
export function createMemberMap({ elementId, members, maxZoom, isPublic = false, cluster = false }) {
    const map = L.map(elementId, { maxZoom }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom,
    }).addTo(map);

    const markerLayer = createMarkerLayer(cluster);
    const latLngs = [];

    members.forEach((member) => {
        const position = [parseFloat(member.lat), parseFloat(member.lng)];
        L.marker(position)
            .bindPopup(createPopupContent(member, isPublic))
            .addTo(markerLayer);
        latLngs.push(position);
    });

    map.addLayer(markerLayer);

    if (latLngs.length > 0) {
        map.fitBounds(latLngs);
    }

    // Images are only in the DOM once the popup is open
    map.on('popupopen', (event) => watchAvatarFallbacks(event.popup.getElement()));

    return map;
}