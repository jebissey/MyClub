// locationStr ("lat,lng") and mapMaxZoom are defined by the template
document.addEventListener('DOMContentLoaded', () => {
    const [lat, lng] = locationStr.split(',').map(parseFloat);
    if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
    }

    const map = L.map('map', {
        maxZoom: mapMaxZoom,
        zoomControl: false
    }).setView([lat, lng], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: mapMaxZoom
    }).addTo(map);

    L.marker([lat, lng]).addTo(map);
});