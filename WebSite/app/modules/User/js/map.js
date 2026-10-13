import { createMemberMap } from '/app/modules/Common/js/memberMap.js';

// locationData, isPublicMap and mapMaxZoom are defined by the template
createMemberMap({
    elementId: 'memberMap',
    members: locationData,
    maxZoom: mapMaxZoom,
    isPublic: isPublicMap,
    cluster: true,
});