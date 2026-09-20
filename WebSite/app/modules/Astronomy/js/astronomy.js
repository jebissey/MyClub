/**
 * Module Astronomie – affichage soleil / lune / planètes à l’œil nu
 * Regard vers le Sud : Est à gauche (−), Ouest à droite (+), azimut > ±90° possible.
 */

const BODIES = [
    { name: 'Sun',     label: 'Soleil',   color: '#ffdd44', size: 18, nakedEye: true },
    { name: 'Moon',    label: 'Lune',     color: '#e0e0e0', size: 14, nakedEye: true },
    { name: 'Mercury', label: 'Mercure',  color: '#b0b0b0', size: 8,  nakedEye: true },
    { name: 'Venus',   label: 'Vénus',    color: '#f5e6c8', size: 10, nakedEye: true },
    { name: 'Mars',    label: 'Mars',     color: '#ff6b4a', size: 9,  nakedEye: true },
    { name: 'Jupiter', label: 'Jupiter',  color: '#e8c48a', size: 12, nakedEye: true },
    { name: 'Saturn',  label: 'Saturne',  color: '#f0d090', size: 11, nakedEye: true },
];

// Limite d’azimut affichée (au-delà de ±90° pour l’été)
const AZIMUTH_LIMIT = 120; // degrés depuis le Sud

let observer = null;
let currentDate = new Date();

function init() {
    const cfg = window.astroConfig || {};
    const lat = cfg.latitude ?? 48.8566;
    const lng = cfg.longitude ?? 2.3522;

    observer = new Astronomy.Observer(lat, lng, 0);

    document.getElementById('currentDateLabel').textContent =
        currentDate.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    setupLocationForm();
    setupTimeSlider();
    refreshAll();
}

/* ---------- Localisation ---------- */

function setupLocationForm() {
    const form = document.getElementById('locationForm');
    const btnGeo = document.getElementById('btnGeo');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        const name = document.getElementById('locName').value.trim();

        if (isNaN(lat) || isNaN(lng)) return;

        observer = new Astronomy.Observer(lat, lng, 0);
        await saveLocation(lat, lng, name);
        refreshAll();
        setStatus('Localisation enregistrée.');
    });

    btnGeo.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('Géolocalisation non supportée.');
            return;
        }
        setStatus('Recherche de la position…');
        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                document.getElementById('lat').value = lat.toFixed(5);
                document.getElementById('lng').value = lng.toFixed(5);
                document.getElementById('locName').value = 'Ma position';
                observer = new Astronomy.Observer(lat, lng, 0);
                await saveLocation(lat, lng, 'Ma position');
                refreshAll();
                setStatus('Position GPS enregistrée.');
            },
            () => setStatus('Impossible d’obtenir la position.'),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
}

async function saveLocation(lat, lng, name) {
    const url = window.astroConfig?.saveUrl || '/astronomy/saveLocation';
    try {
        const body = new URLSearchParams({ lat, lng, name });
        await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });
    } catch (e) {
        console.warn('Cookie save failed', e);
    }
}

function setStatus(msg) {
    const el = document.getElementById('locStatus');
    if (el) el.textContent = msg;
}

/* ---------- Curseur d’heure ---------- */

function setupTimeSlider() {
    const slider = document.getElementById('timeSlider');
    const label  = document.getElementById('timeLabel');

    // Position initiale = heure actuelle
    const now = new Date();
    const minutes = now.getHours() * 60 + now.getMinutes();
    slider.value = minutes;
    updateTimeLabel(minutes);

    slider.addEventListener('input', () => {
        const mins = parseInt(slider.value, 10);
        updateTimeLabel(mins);
        updateSky(mins);
        updatePlanetDetails(mins);
    });
}

function updateTimeLabel(totalMinutes) {
    const h = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;
    document.getElementById('timeLabel').textContent =
        String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
}

function dateAtMinutes(totalMinutes) {
    const d = new Date(currentDate);
    d.setHours(0, 0, 0, 0);
    d.setMinutes(totalMinutes);
    return d;
}

/* ---------- Calculs Astronomy Engine ---------- */

function getEquatorial(bodyName, date) {
    return Astronomy.Equator(bodyName, date, observer, true, true);
}

function getHorizontal(bodyName, date) {
    const equ = getEquatorial(bodyName, date);
    return Astronomy.Horizon(date, observer, equ.ra, equ.dec, 'normal');
}

/**
 * Azimut astronomique → azimut « regard Sud »
 * Astronomy Engine : 0 = Nord, 90 = Est, 180 = Sud, 270 = Ouest
 * Nous voulons : 0 = Sud, positif = Ouest, négatif = Est
 */
function toSouthAzimuth(azFromNorth) {
    // azFromNorth ∈ [0, 360)
    let az = azFromNorth - 180; // maintenant 0 = Sud, +90 = Ouest, -90 = Est
    if (az > 180) az -= 360;
    if (az < -180) az += 360;
    return az;
}

function searchRiseSet(bodyName, direction /* +1 rise, -1 set */) {
    // direction : +1 = lever, -1 = coucher
    try {
        const start = new Date(currentDate);
        start.setHours(0, 0, 0, 0);
        const result = Astronomy.SearchRiseSet(bodyName, observer, direction, start, 1);
        return result ? result.date : null;
    } catch (e) {
        return null;
    }
}

function formatTime(date) {
    if (!date) return '—';
    return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

/* ---------- Tableau lever / coucher ---------- */

function refreshRiseSetTable() {
    const tbody = document.querySelector('#riseSetTable tbody');
    tbody.innerHTML = '';

    for (const body of BODIES) {
        const rise = searchRiseSet(body.name, +1);
        const set  = searchRiseSet(body.name, -1);

        // Visible maintenant ?
        const now = new Date();
        const hor = getHorizontal(body.name, now);
        const visible = hor.altitude > 0;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <span class="badge me-1" style="background:${body.color}; width:12px; height:12px; display:inline-block;"></span>
                ${body.label}
            </td>
            <td>${formatTime(rise)}</td>
            <td>${formatTime(set)}</td>
            <td>${visible ? '✓' : '—'}</td>
        `;
        tbody.appendChild(tr);
    }
}

/* ---------- Ciel animé ---------- */

function updateSky(totalMinutes) {
    const container = document.getElementById('skyObjects');
    container.innerHTML = '';

    const date = dateAtMinutes(totalMinutes);
    const width = container.clientWidth;
    const height = container.clientHeight;

    // Zone d’azimut : -AZIMUTH_LIMIT … +AZIMUTH_LIMIT → 0 … width
    const azToX = (az) => {
        const clamped = Math.max(-AZIMUTH_LIMIT, Math.min(AZIMUTH_LIMIT, az));
        return ((clamped + AZIMUTH_LIMIT) / (2 * AZIMUTH_LIMIT)) * width;
    };

    // Altitude 0 → bas, 90 → haut (on limite un peu pour ne pas coller au bord)
    const altToY = (alt) => {
        const a = Math.max(0, Math.min(90, alt));
        return height - (a / 90) * (height * 0.92) - 8;
    };

    for (const body of BODIES) {
        const hor = getHorizontal(body.name, date);
        const az  = toSouthAzimuth(hor.azimuth);
        const alt = hor.altitude;

        // On affiche même légèrement sous l’horizon pour les levers/couchers
        if (alt < -5) continue;

        const x = azToX(az);
        const y = altToY(Math.max(0, alt));

        const el = document.createElement('div');
        el.className = 'sky-body position-absolute rounded-circle d-flex align-items-center justify-content-center';
        el.style.cssText = `
            left: ${x}px;
            top: ${y}px;
            width: ${body.size}px;
            height: ${body.size}px;
            background: ${body.color};
            transform: translate(-50%, -50%);
            box-shadow: 0 0 8px ${body.color};
            font-size: 9px;
            color: #111;
            z-index: 5;
            cursor: default;
        `;
        el.title = `${body.label}\nAlt: ${alt.toFixed(1)}°\nAz (Sud): ${az.toFixed(1)}°`;
        el.textContent = body.label[0];
        container.appendChild(el);
    }
}

/* ---------- Détails planètes ---------- */

function updatePlanetDetails(totalMinutes) {
    const tbody = document.querySelector('#planetDetailsTable tbody');
    tbody.innerHTML = '';

    const date = dateAtMinutes(totalMinutes);

    for (const body of BODIES) {
        if (!body.nakedEye) continue;

        const hor = getHorizontal(body.name, date);
        const az  = toSouthAzimuth(hor.azimuth);

        let mag = '—';
        let illum = '—';

        try {
            if (body.name === 'Moon') {
                const ill = Astronomy.Illumination(body.name, date);
                mag = ill.mag.toFixed(1);
                illum = (ill.phase_fraction * 100).toFixed(0) + ' %';
            } else if (body.name !== 'Sun') {
                const ill = Astronomy.Illumination(body.name, date);
                mag = ill.mag.toFixed(1);
                if (ill.phase_fraction !== undefined) {
                    illum = (ill.phase_fraction * 100).toFixed(0) + ' %';
                }
            }
        } catch (_) { /* ignore */ }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <span class="badge me-1" style="background:${body.color}; width:10px; height:10px; display:inline-block;"></span>
                ${body.label}
            </td>
            <td>${hor.altitude.toFixed(1)}°</td>
            <td>${az.toFixed(1)}°</td>
            <td>${mag}</td>
            <td>${illum}</td>
        `;
        tbody.appendChild(tr);
    }
}

/* ---------- Refresh global ---------- */

function refreshAll() {
    const slider = document.getElementById('timeSlider');
    const mins = parseInt(slider.value, 10);
    refreshRiseSetTable();
    updateSky(mins);
    updatePlanetDetails(mins);
}

/* ---------- Boot ---------- */

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
