import { createMemberMap } from '/app/modules/Common/js/memberMap.js';

// Lazy-initialises the carpooling map the first time the accordion is opened.
// Leaflet cannot compute its size inside a collapsed container, so the map is
// created on "shown.bs.collapse" and resized once the container is visible.
// carpoolData and carpoolMaxZoom are defined by the template.

const collapseElement = document.getElementById('carpoolCollapse');
let map = null;

if (collapseElement !== null) {
    collapseElement.addEventListener('shown.bs.collapse', () => {
        if (map === null) {
            map = createMemberMap({
                elementId: 'carpoolMap',
                members: carpoolData,
                maxZoom: carpoolMaxZoom,
            });
        }
        // Recompute the size now that the container is visible
        map.invalidateSize();
    });
}