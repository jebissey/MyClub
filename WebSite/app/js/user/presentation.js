document.addEventListener('DOMContentLoaded', function () {
    if (locationStr) {
        try {
            const [lat, lng] = locationStr.split(',');
            if (lat && lng) {
                const map = L.map('map', {
                    maxZoom: 10,
                    zoomControl: false
                }).setView([parseFloat(lat), parseFloat(lng)], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 10
                }).addTo(map);

                L.marker([parseFloat(lat), parseFloat(lng)]).addTo(map);
            }
        } catch (e) {
            console.error('Erreur lors du parsing des coordonnées', e);
        }
    }
});
