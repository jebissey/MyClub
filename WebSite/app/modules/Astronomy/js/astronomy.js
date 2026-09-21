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

const AZIMUTH_LIMIT = 120; // degrés depuis le Sud, au-delà de ±90° pour l'été

class AstronomyManager {
    constructor(config = {}) {
        this.observer = new Astronomy.Observer(
            config.latitude ?? 48.8566,
            config.longitude ?? 2.3522,
            0
        );
        this.currentDate = new Date();
    }

    init() {
        document.getElementById('currentDateLabel').textContent =
            this.currentDate.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        this.setupLocationForm();
        this.setupTimeSlider();
        this.refreshAll();
    }

    // ── Localisation ─────────────────────────────────────────────────────

    setupLocationForm() {
        const btnGeo = document.getElementById('btnGeo');
        btnGeo.addEventListener('click', () => this.handleGeolocation());
        // Le formulaire lat/lng/nom se soumet nativement vers /astronomy/save.
    }

    handleGeolocation() {
        if (!navigator.geolocation) {
            this.setStatus('Géolocalisation non supportée.');
            return;
        }
        this.setStatus('Recherche de la position…');
        navigator.geolocation.getCurrentPosition(
            (pos) => this.onGeolocationSuccess(pos),
            () => this.setStatus('Impossible d’obtenir la position.'),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    onGeolocationSuccess(pos) {
        document.getElementById('lat').value = pos.coords.latitude.toFixed(5);
        document.getElementById('lng').value = pos.coords.longitude.toFixed(5);
        document.getElementById('locName').value = 'Ma position';
        document.getElementById('locationForm').requestSubmit();
    }

    setStatus(msg) {
        const el = document.getElementById('locStatus');
        if (el) el.textContent = msg;
    }

    // ── Curseur d'heure ──────────────────────────────────────────────────

    setupTimeSlider() {
        const slider = document.getElementById('timeSlider');
        const now = new Date();
        const minutes = now.getHours() * 60 + now.getMinutes();

        slider.value = minutes;
        this.updateTimeLabel(minutes);

        slider.addEventListener('input', () => {
            const mins = parseInt(slider.value, 10);
            this.updateTimeLabel(mins);
            this.updateSky(mins);
            this.updatePlanetDetails(mins);
        });
    }

    updateTimeLabel(totalMinutes) {
        const h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        document.getElementById('timeLabel').textContent =
            String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    dateAtMinutes(totalMinutes) {
        const d = new Date(this.currentDate);
        d.setHours(0, 0, 0, 0);
        d.setMinutes(totalMinutes);
        return d;
    }

    // ── Calculs Astronomy Engine ─────────────────────────────────────────

    getEquatorial(bodyName, date) {
        return Astronomy.Equator(bodyName, date, this.observer, true, true);
    }

    getHorizontal(bodyName, date) {
        const equ = this.getEquatorial(bodyName, date);
        return Astronomy.Horizon(date, this.observer, equ.ra, equ.dec, 'normal');
    }

    /**
     * Azimut astronomique → azimut « regard Sud »
     * Astronomy Engine : 0 = Nord, 90 = Est, 180 = Sud, 270 = Ouest
     * Nous voulons : 0 = Sud, positif = Ouest, négatif = Est
     */
    toSouthAzimuth(azFromNorth) {
        let az = azFromNorth - 180;
        if (az > 180) az -= 360;
        if (az < -180) az += 360;
        return az;
    }

    searchRiseSet(bodyName, direction /* +1 rise, -1 set */) {
        try {
            const start = new Date(this.currentDate);
            start.setHours(0, 0, 0, 0);
            const result = Astronomy.SearchRiseSet(bodyName, this.observer, direction, start, 1);
            return result ? result.date : null;
        } catch (e) {
            return null;
        }
    }

    formatTime(date) {
        if (!date) return '—';
        return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    }

    // ── Tableau lever / coucher ──────────────────────────────────────────

    refreshRiseSetTable() {
        const tbody = document.querySelector('#riseSetTable tbody');
        tbody.innerHTML = '';

        for (const body of BODIES) {
            const rise = this.searchRiseSet(body.name, +1);
            const set  = this.searchRiseSet(body.name, -1);

            const now = new Date();
            const hor = this.getHorizontal(body.name, now);
            const visible = hor.altitude > 0;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <span class="badge me-1" style="background:${body.color}; width:12px; height:12px; display:inline-block;"></span>
                    ${body.label}
                </td>
                <td>${this.formatTime(rise)}</td>
                <td>${this.formatTime(set)}</td>
                <td>${visible ? '✓' : '—'}</td>
            `;
            tbody.appendChild(tr);
        }
    }

    // ── Ciel animé ───────────────────────────────────────────────────────

    updateSky(totalMinutes) {
        const container = document.getElementById('skyObjects');
        container.innerHTML = '';

        const date = this.dateAtMinutes(totalMinutes);
        const width = container.clientWidth;
        const height = container.clientHeight;

        const azToX = (az) => {
            const clamped = Math.max(-AZIMUTH_LIMIT, Math.min(AZIMUTH_LIMIT, az));
            return ((clamped + AZIMUTH_LIMIT) / (2 * AZIMUTH_LIMIT)) * width;
        };

        const altToY = (alt) => {
            const a = Math.max(0, Math.min(90, alt));
            return height - (a / 90) * (height * 0.92) - 8;
        };

        for (const body of BODIES) {
            const hor = this.getHorizontal(body.name, date);
            const az  = this.toSouthAzimuth(hor.azimuth);
            const alt = hor.altitude;

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

    // ── Détails planètes ─────────────────────────────────────────────────

    updatePlanetDetails(totalMinutes) {
        const tbody = document.querySelector('#planetDetailsTable tbody');
        tbody.innerHTML = '';

        const date = this.dateAtMinutes(totalMinutes);

        for (const body of BODIES) {
            if (!body.nakedEye) continue;

            const hor = this.getHorizontal(body.name, date);
            const az  = this.toSouthAzimuth(hor.azimuth);

            let mag = '—';
            let illum = '—';

            try {
                if (body.name !== 'Sun') {
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

    // ── Refresh global ───────────────────────────────────────────────────

    refreshAll() {
        const slider = document.getElementById('timeSlider');
        const mins = parseInt(slider.value, 10);
        this.refreshRiseSetTable();
        this.updateSky(mins);
        this.updatePlanetDetails(mins);
    }
}

const astronomy = new AstronomyManager(window.astroConfig ?? {});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => astronomy.init());
} else {
    astronomy.init();
}
