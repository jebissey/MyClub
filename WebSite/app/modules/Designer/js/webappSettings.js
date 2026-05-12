import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

// ── Métadonnées des sections ──────────────────────────────────────────────
const SECTIONS = {
    header: { color: '#0d6efd', label: 'En-tête', hasTiny: true },
    article: { color: '#198754', label: 'Article principal', hasTiny: false },
    latest: { color: '#ffc107', label: 'Derniers articles', hasTiny: false },
    footer: { color: '#6f42c1', label: 'Pied de page', hasTiny: true },
    images: { color: '#d63384', label: 'Images', hasTiny: false },
};

let activeSection = null;
const tinyInitialized = {};

// ── Activation d'une section ──────────────────────────────────────────────
function activateSection(key) {
    if (activeSection) {
        document.getElementById('prev-' + activeSection)?.classList.remove('active');
        const prevEditor = document.getElementById('editor-' + activeSection);
        if (prevEditor) {
            prevEditor.classList.remove('d-block', 'd-flex');
            prevEditor.classList.add('d-none');
        }
        document.querySelectorAll('.nav-pills .nav-link').forEach(el => {
            if (el.getAttribute('onclick') === `activateSection('${activeSection}')`)
                el.classList.remove('active');
        });
    }

    activeSection = key;
    const meta = SECTIONS[key];

    document.getElementById('prev-' + key)?.classList.add('active');
    const editorEl = document.getElementById('editor-' + key);
    if (editorEl) {
        editorEl.classList.remove('d-none');
        editorEl.classList.add(meta.hasTiny ? 'd-flex' : 'd-block');
    }
    document.getElementById('editor-placeholder')?.classList.add('d-none');

    document.getElementById('editor-section-icon').style.background = meta.color;
    document.getElementById('editor-section-title').textContent = meta.label;

    document.querySelectorAll('.nav-pills .nav-link').forEach(el => {
        if (el.getAttribute('onclick') === `activateSection('${key}')`)
            el.classList.add('active');
    });

    // Initialisation paresseuse de TinyMCE en mode permissif (l'élément doit être visible)
    if (meta.hasTiny && !tinyInitialized[key]) {
        tinyInitialized[key] = true;
        initTinyMCE('#tinymce-' + key, { mode: 'permissive' });
    }

    if (key === 'article') refreshArticleStatus();
    if (key === 'latest') refreshLatestStatus();
}

// ── Mise à jour live : article principal ─────────────────────────────────
function refreshArticleStatus() {
    const id = parseInt(document.getElementById('input-featured-article')?.value) || 0;
    const paragraphs = parseInt(document.getElementById('input-featured-paragraphs')?.value) || 0;
    const statusEl = document.getElementById('article-status');
    const prevEl = document.getElementById('preview-article-content');

    const paraLabel = paragraphs === 0
        ? `<span class="text-muted">(article entier)</span>`
        : `<span class="text-muted">(${paragraphs} paragraphe${paragraphs > 1 ? 's' : ''})</span>`;

    if (id > 0) {
        statusEl.className = 'alert alert-success mb-0';
        statusEl.innerHTML = `<i class="bi bi-file-earmark-check me-1"></i>L'article <strong>#${id}</strong> sera affiché en page d'accueil. ${paraLabel}`;
        if (prevEl) prevEl.innerHTML =
            `<div class="d-flex align-items-center gap-2 small text-success fw-semibold">` +
            `<i class="bi bi-file-earmark-text"></i> Article ID&nbsp;<strong>${id}</strong>&nbsp;${paraLabel}</div>`;
    } else {
        statusEl.className = 'alert alert-info mb-0';
        statusEl.innerHTML = `<i class="bi bi-info-circle me-1"></i>Le dernier article publié sera affiché. ${paraLabel}`;
        if (prevEl) prevEl.innerHTML =
            `<div class="d-flex flex-column gap-1">` +
            `<div class="rounded-1 bg-secondary-subtle" style="width:80%;height:8px;"></div>` +
            `<div class="rounded-1 bg-secondary-subtle" style="width:95%;height:8px;"></div>` +
            `<div class="rounded-1 bg-secondary-subtle" style="width:70%;height:8px;"></div>` +
            `<span class="fst-italic text-muted d-flex align-items-center gap-1 small mt-1">` +
            `<i class="bi bi-arrow-return-right"></i>Dernier article&nbsp;${paraLabel}</span></div>`;
    }
}

// ── Mise à jour live : derniers articles ──────────────────────────────────
function refreshLatestStatus() {
    const count = parseInt(document.getElementById('input-latest-count')?.value) || 0;
    const statusEl = document.getElementById('latest-count-status');
    const prevEl = document.getElementById('preview-latest-content');

    if (statusEl) {
        statusEl.className = count === 0 ? 'alert alert-warning mb-0' : 'alert alert-success mb-0';
        statusEl.innerHTML = count === 0
            ? `<i class="bi bi-eye-slash me-1"></i>La section « derniers articles » <strong>ne sera pas affichée</strong>.`
            : `<i class="bi bi-check-circle me-1"></i>Les <strong>${count}</strong> derniers articles seront listés.`;
    }

    if (prevEl) {
        if (count === 0) {
            prevEl.innerHTML = `<span class="fst-italic text-muted d-flex align-items-center gap-1 small"><i class="bi bi-eye-slash"></i> Section masquée</span>`;
        } else {
            const shown = Math.min(count, 6);
            let html = '';
            for (let i = 0; i < shown; i++) {
                html += `<div class="d-flex justify-content-between align-items-center py-1 border-bottom">` +
                    `<div class="rounded-1 bg-secondary-subtle" style="width:65%;height:8px;"></div>` +
                    `<span class="badge bg-secondary rounded-pill" style="font-size:.6rem;">01/01</span></div>`;
            }
            if (count > 6) html += `<div class="text-center text-muted mt-1" style="font-size:.62rem;">+ ${count - 6} autres…</div>`;
            prevEl.innerHTML = html;
        }
    }
}

// ── Gestion des images ────────────────────────────────────────────────────

/**
 * Redimensionne une image via Canvas selon la stratégie choisie.
 *
 * @param {File}   file        Fichier image source
 * @param {Object} opts
 * @param {number} opts.maxW   Largeur max (px)
 * @param {number} opts.maxH   Hauteur max (px)
 * @param {'fit'|'cover'|'exact'} opts.mode
 *   - 'fit'   : redimensionne pour tenir dans maxW×maxH, conserve le ratio
 *   - 'cover' : recadre au centre pour remplir exactement maxW×maxH
 *   - 'exact' : force exactement maxW×maxH (étire si nécessaire)
 * @param {'image/png'|'image/jpeg'} opts.mimeType
 * @param {number} [opts.quality=0.92]  Qualité JPEG (0–1)
 * @returns {Promise<{dataURL: string, width: number, height: number, sizeKB: number}>}
 */
function resizeImage(file, { maxW, maxH, mode = 'fit', mimeType = 'image/png', quality = 0.92 }) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(url);

            let srcX = 0, srcY = 0, srcW = img.width, srcH = img.height;
            let dstW, dstH;

            if (mode === 'exact') {
                dstW = maxW; dstH = maxH;
            } else if (mode === 'fit') {
                const ratio = Math.min(maxW / img.width, maxH / img.height, 1);
                dstW = Math.round(img.width * ratio);
                dstH = Math.round(img.height * ratio);
            } else if (mode === 'cover') {
                // Calcule le crop centré
                dstW = maxW; dstH = maxH;
                const scale = Math.max(maxW / img.width, maxH / img.height);
                const scaledW = img.width * scale;
                const scaledH = img.height * scale;
                srcX = (img.width - scaledW / scale) / 2;
                srcY = (img.height - scaledH / scale) / 2;
                srcW = img.width - srcX * 2;
                srcH = img.height - srcY * 2;
            }

            const canvas = document.createElement('canvas');
            canvas.width = dstW;
            canvas.height = dstH;
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, srcX, srcY, srcW, srcH, 0, 0, dstW, dstH);

            const dataURL = canvas.toDataURL(mimeType, quality);
            // Estimation de la taille en ko (base64 → octets)
            const sizeKB = Math.round((dataURL.length * 3 / 4) / 1024);
            resolve({ dataURL, width: dstW, height: dstH, sizeKB });
        };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Impossible de lire l\'image.')); };
        img.src = url;
    });
}

/** Configs par clé */
const IMAGE_CONFIGS = {
    home: {
        maxW: 48, maxH: 48,
        mode: 'cover',          // carré exact centré
        mimeType: 'image/png',
        previewStyle: '',       // taille naturelle (64px wrap)
    },
    logo: {
        maxW: 1200, maxH: 1200,
        mode: 'fit',            // conserve le ratio, réduit si besoin
        mimeType: 'image/png',
        previewStyle: 'max-height:64px;',
    },
    banner: {
        maxW: 1920, maxH: 600,
        mode: 'fit',            // conserve le ratio, réduit si besoin
        mimeType: 'image/jpeg',
        quality: 0.88,
        previewStyle: 'width:100%;height:64px;object-fit:cover;',
    },
};

/**
 * Affiche le badge de statut dans la card.
 * @param {string} key   'home'|'logo'|'banner'
 * @param {'pending'|'ok'|'error'} state
 * @param {string} [msg]
 */
function setImageInfo(key, state, msg = '') {
    const el = document.getElementById('info-' + key);
    if (!el) return;
    const map = {
        pending: 'alert-info',
        ok: 'alert-success',
        error: 'alert-danger',
    };
    el.className = `alert ${map[state] ?? 'alert-secondary'} py-1 px-2 small mb-0`;
    el.innerHTML = msg;
}

/**
 * Traite un fichier image pour une clé donnée :
 * redimensionne, affiche la prévisualisation, stocke le dataURL dans le hidden input.
 */
async function handleImageFile(key, file) {
    const cfg = IMAGE_CONFIGS[key];
    if (!cfg) return;

    setImageInfo(key, 'pending', `<i class="bi bi-hourglass-split me-1"></i>${window.t('imageProcessing')}`);

    try {
        const { dataURL, width, height, sizeKB } = await resizeImage(file, cfg);

        // Prévisualisation dans le panneau éditeur
        const previewEl = document.getElementById('preview-' + key);
        const placeholderEl = document.getElementById('placeholder-' + key);
        const dotEl = document.getElementById('dot-' + key);

        if (previewEl) {
            previewEl.src = dataURL;
            previewEl.style.cssText = cfg.previewStyle;
            previewEl.style.display = '';
        }
        if (placeholderEl) placeholderEl.style.display = 'none';
        if (dotEl) dotEl.style.background = '#ffc107'; // jaune = modifié, pas encore sauvegardé

        // Pour la bannière : mettre à jour aussi la zone en haut de la colonne gauche
        if (key === 'banner') {
            const thumb = document.getElementById('prev-thumb-banner');
            if (thumb) {
                if (thumb.tagName === 'IMG') {
                    thumb.src = dataURL;
                    thumb.style.display = '';
                    thumb.style.cssText = 'width:100%;height:50px;object-fit:cover;display:block;';
                } else {
                    // Le placeholder était un div (pas de bannière existante) → le remplacer par une img
                    const img = document.createElement('img');
                    img.id = 'prev-thumb-banner';
                    img.src = dataURL;
                    img.alt = '';
                    img.style.cssText = 'width:100%;height:50px;object-fit:cover;display:block;';
                    thumb.replaceWith(img);
                }
            }
        }

        // Stocker la donnée dans le champ caché
        const hiddenEl = document.getElementById('hidden-' + key);
        if (hiddenEl) hiddenEl.value = dataURL;
        setImageInfo(key, 'ok',
            `<i class="bi bi-check-circle me-1"></i>${width} × ${height} px — ${sizeKB} ko` +
            `<span class="ms-2 text-warning fw-semibold"><i class="bi bi-floppy me-1"></i>${window.t('imageToSave')}</span>`
        );
    } catch (err) {
        setImageInfo(key, 'error', `<i class="bi bi-exclamation-triangle me-1"></i>${window.t('imageReadError')}`);
    }
}

/** Initialise les file inputs et le drag-and-drop pour une card image. */
function initImageUpload(key) {
    const fileInput = document.getElementById('file-' + key);
    const card = document.getElementById('upload-card-' + key);
    if (!fileInput || !card) return;

    // Clic sur la card → ouvre le sélecteur de fichier
    // (l'input est positionné en absolu sur toute la card, donc le clic arrive directement)

    fileInput.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file) handleImageFile(key, file);
        fileInput.value = ''; // reset pour permettre de re-sélectionner le même fichier
    });

    // Drag-and-drop
    card.addEventListener('dragover', e => {
        e.preventDefault();
        card.classList.add('drag-over');
    });
    card.addEventListener('dragleave', () => card.classList.remove('drag-over'));
    card.addEventListener('drop', e => {
        e.preventDefault();
        card.classList.remove('drag-over');
        const file = e.dataTransfer?.files?.[0];
        if (file && file.type.startsWith('image/')) handleImageFile(key, file);
    });
}

// ── Roue chromatique ───────────────────────────────────────────────────────
function initColorWheel() {
    const canvas = document.getElementById('color-wheel');
    const panel = document.getElementById('color-wheel-panel');
    const btnToggle = document.getElementById('btn-toggle-wheel');
    const iconToggle = document.getElementById('icon-toggle-wheel');
    if (!canvas || !panel) return;

    const ctx = canvas.getContext('2d');
    const SIZE = 160, CX = SIZE / 2, CY = SIZE / 2, R = SIZE / 2 - 4;

    // ── Conversions couleur ──────────────────────────────────────────────
    function hexToHsl(hex) {
        let r = parseInt(hex.slice(1, 3), 16) / 255,
            g = parseInt(hex.slice(3, 5), 16) / 255,
            b = parseInt(hex.slice(5, 7), 16) / 255;
        const mx = Math.max(r, g, b), mn = Math.min(r, g, b);
        let h, s, l = (mx + mn) / 2;
        if (mx === mn) { h = s = 0; } else {
            const d = mx - mn;
            s = l > .5 ? d / (2 - mx - mn) : d / (mx + mn);
            switch (mx) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                case g: h = ((b - r) / d + 2) / 6; break;
                default: h = ((r - g) / d + 4) / 6;
            }
        }
        return [h * 360, s * 100, l * 100];
    }

    function hslToHex(h, s, l) {
        s /= 100; l /= 100;
        const a = s * Math.min(l, 1 - l);
        const f = n => { const k = (n + h / 30) % 12; return l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1)); };
        return '#' + [f(0), f(8), f(4)].map(x => Math.round(x * 255).toString(16).padStart(2, '0')).join('');
    }

    function hslToPos(h, s) {
        const angle = (h - 90) * Math.PI / 180;
        const dist = (s / 100) * R;
        return { x: CX + dist * Math.cos(angle), y: CY + dist * Math.sin(angle) };
    }

    // ── Harmonies ────────────────────────────────────────────────────────
    const HARMONIES = [
        { key: 'comp', name: 'Complémentaire', fn: (h, s, l) => [[h, s, l], [(h + 180) % 360, s, Math.min(l + 20, 90)], [(h + 180) % 360, s, Math.max(l - 15, 10)]] },
        { key: 'ana', name: 'Analogue', fn: (h, s, l) => [[h, s, l], [(h + 30) % 360, s, l], [(h - 30 + 360) % 360, s, l]] },
        { key: 'tri', name: 'Triadique', fn: (h, s, l) => [[h, s, l], [(h + 120) % 360, s, l], [(h + 240) % 360, s, l]] },
        { key: 'split', name: 'Split-comp.', fn: (h, s, l) => [[h, s, l], [(h + 150) % 360, s, l], [(h + 210) % 360, s, l]] },
        { key: 'sq', name: 'Carré', fn: (h, s, l) => [[h, s, l], [(h + 90) % 360, s, l], [(h + 270) % 360, s, l]] },
        { key: 'mono', name: 'Monochrome', fn: (h, s, l) => [[h, s, Math.min(l + 30, 90)], [h, s, l], [h, Math.max(s - 30, 10), Math.max(l - 30, 15)]] },
    ];

    // ── Dessin de la roue ────────────────────────────────────────────────
    function drawWheel() {
        ctx.clearRect(0, 0, SIZE, SIZE);
        for (let deg = 0; deg < 360; deg++) {
            const g = ctx.createRadialGradient(CX, CY, 0, CX, CY, R);
            g.addColorStop(0, '#fff');
            g.addColorStop(1, `hsl(${deg},100%,50%)`);
            ctx.beginPath();
            ctx.moveTo(CX, CY);
            ctx.arc(CX, CY, R, (deg - 1) * Math.PI / 180, (deg + 1) * Math.PI / 180);
            ctx.closePath();
            ctx.fillStyle = g;
            ctx.fill();
        }
        const dk = ctx.createRadialGradient(CX, CY, R * .55, CX, CY, R);
        dk.addColorStop(0, 'rgba(0,0,0,0)');
        dk.addColorStop(1, 'rgba(0,0,0,.35)');
        ctx.beginPath();
        ctx.arc(CX, CY, R, 0, Math.PI * 2);
        ctx.fillStyle = dk;
        ctx.fill();
    }

    function drawDots() {
        [
            { id: 'input-navbar-bg', label: 'BG' },
            { id: 'input-navbar-ink', label: 'I' },
            { id: 'input-navbar-icon', label: '★' },
        ].forEach(({ id, label }) => {
            const inp = document.getElementById(id);
            if (!inp) return;
            const [h, s] = hexToHsl(inp.value);
            const { x, y } = hslToPos(h, s);
            ctx.beginPath();
            ctx.arc(x, y, 8, 0, Math.PI * 2);
            ctx.fillStyle = inp.value;
            ctx.fill();
            ctx.strokeStyle = '#fff'; ctx.lineWidth = 2.5; ctx.stroke();
            ctx.strokeStyle = 'rgba(0,0,0,.4)'; ctx.lineWidth = 1; ctx.stroke();
            ctx.fillStyle = 'rgba(0,0,0,.75)';
            ctx.font = 'bold 8px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, x, y);
        });
    }

    function renderPresets() {
        const bgInp = document.getElementById('input-navbar-bg');
        if (!bgInp) return;
        const [h, s, l] = hexToHsl(bgInp.value);
        const cont = document.getElementById('harmony-presets');
        cont.innerHTML = '';
        HARMONIES.forEach(({ key, name, fn }) => {
            const cols = fn(h, s, l).map(([ch, cs, cl]) => hslToHex(ch, cs, cl));
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary d-flex flex-column align-items-center gap-1 py-2 px-2';
            btn.style.minWidth = '78px';
            btn.dataset.key = key;
            btn.innerHTML =
                `<div class="d-flex gap-1">${cols.map(c =>
                    `<span style="width:16px;height:16px;background:${c};border-radius:3px;border:1px solid rgba(0,0,0,.12);display:inline-block;"></span>`
                ).join('')}</div>` +
                `<span style="font-size:.65rem;">${name}</span>`;
            btn.addEventListener('click', () => applyHarmony(cols, key));
            cont.appendChild(btn);
        });
    }

    function applyHarmony(cols, key) {
        document.querySelectorAll('#harmony-presets .btn').forEach(b => b.classList.remove('active'));
        document.querySelector(`#harmony-presets [data-key="${key}"]`)?.classList.add('active');
        [
            ['input-navbar-bg', 'label-navbar-bg'],
            ['input-navbar-ink', 'label-navbar-ink'],
            ['input-navbar-icon', 'label-navbar-icon'],
        ].forEach(([inpId, lblId], i) => {
            const inp = document.getElementById(inpId);
            const lbl = document.getElementById(lblId);
            if (inp) {
                inp.value = cols[i];
                if (lbl) lbl.textContent = cols[i];
                inp.dispatchEvent(new Event('input')); // met à jour la preview navbar
            }
        });
        redraw();
    }

    function redraw() {
        drawWheel();
        drawDots();
        renderPresets();
    }

    // ── Ouverture / fermeture ────────────────────────────────────────────
    btnToggle?.addEventListener('click', () => {
        panel.classList.toggle('d-none');
        if (iconToggle) {
            iconToggle.className = panel.classList.contains('d-none')
                ? 'bi bi-chevron-down'
                : 'bi bi-chevron-up';
        }
        if (!panel.classList.contains('d-none')) redraw();
    });

    // ── Mise à jour live quand l'utilisateur change une couleur ──────────
    ['input-navbar-bg', 'input-navbar-ink', 'input-navbar-icon'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            if (!panel.classList.contains('d-none')) redraw();
        });
    });
}


function hexToRgb(hex) {
    hex = hex.replace('#', '');

    if (hex.length === 3) {
        hex = hex.split('').map(c => c + c).join('');
    }

    const num = parseInt(hex, 16);

    return {
        r: (num >> 16) & 255,
        g: (num >> 8) & 255,
        b: num & 255
    };
}

function luminance(r, g, b) {
    const a = [r, g, b].map(v => {
        v /= 255;

        return v <= 0.03928
            ? v / 12.92
            : Math.pow((v + 0.055) / 1.055, 2.4);
    });

    return 0.2126 * a[0]
        + 0.7152 * a[1]
        + 0.0722 * a[2];
}

function contrastRatio(color1, color2) {
    const c1 = hexToRgb(color1);
    const c2 = hexToRgb(color2);

    const l1 = luminance(c1.r, c1.g, c1.b);
    const l2 = luminance(c2.r, c2.g, c2.b);

    const brightest = Math.max(l1, l2);
    const darkest = Math.min(l1, l2);

    return ((brightest + 0.05) / (darkest + 0.05)).toFixed(2);
}

function updateNavbarContrast() {
    const bg = document.getElementById('input-navbar-bg').value;
    const ink = document.getElementById('input-navbar-ink').value;

    const ratio = parseFloat(contrastRatio(bg, ink));

    const info = document.getElementById('navbar-contrast-info');

    let badge = '';
    let cls = '';

    if (ratio >= 7) {
        badge = 'Excellent';
        cls = 'text-success';
    } else if (ratio >= 4.5) {
        badge = 'Correct';
        cls = 'text-warning';
    } else {
        badge = 'Insuffisant';
        cls = 'text-danger';
    }

    info.className = `small mt-2 fw-semibold ${cls}`;

    info.innerHTML = `
        <i class="bi bi-circle-fill me-1"></i>
        Contraste : ${ratio}:1 — ${badge}
    `;
}

document.getElementById('input-navbar-bg')
    .addEventListener('input', updateNavbarContrast);

document.getElementById('input-navbar-ink')
    .addEventListener('input', updateNavbarContrast);

updateNavbarContrast();


// ── Initialisation au chargement ──────────────────────────────────────────
// type="module" est différé par défaut : le DOM est déjà prêt à ce stade.

document.getElementById('input-featured-article')
    ?.addEventListener('input', refreshArticleStatus);

const countInput = document.getElementById('input-latest-count');
const countSlider = document.getElementById('slider-latest-count');
if (countInput && countSlider) {
    countInput.addEventListener('input', () => { countSlider.value = countInput.value; refreshLatestStatus(); });
    countSlider.addEventListener('input', () => { countInput.value = countSlider.value; refreshLatestStatus(); });
}

document.getElementById('settingsForm')?.addEventListener('submit', () => {
    if (typeof tinymce !== 'undefined') tinymce.triggerSave();
});

document.getElementById('saveLanguage')?.addEventListener('click', () => {
    const lang = document.getElementById('languageSelect').value;
    const useLang = document.getElementById('useLanguage').checked ? 1 : 0;
    window.location.href = `/settings-language?lang=${encodeURIComponent(lang)}&use_language=${useLang}`;
});

document.getElementById('input-featured-paragraphs')
    ?.addEventListener('input', refreshArticleStatus);

// Initialisation des uploads d'images
['home', 'logo', 'banner'].forEach(initImageUpload);

initColorWheel();

// ── Couleurs navbar ────────────────────────────────────────────────────────
(function initNavbarColors() {
    const navbarPreview = document.getElementById('prev-navbar');
    const navbarIcons = document.getElementById('prev-navbar-icons');

    // Couleurs initiales depuis les data attributes
    if (navbarPreview) {
        navbarPreview.style.backgroundColor = navbarPreview.dataset.bg ?? '#212529';
        navbarPreview.style.color = navbarPreview.dataset.ink ?? '#ffffff';
    }
    if (navbarIcons && navbarPreview) {
        navbarIcons.style.color = navbarPreview.dataset.icon ?? '#ffc107';
    }

    const inputs = {
        bg: { input: document.getElementById('input-navbar-bg'), label: document.getElementById('label-navbar-bg') },
        ink: { input: document.getElementById('input-navbar-ink'), label: document.getElementById('label-navbar-ink') },
        icon: { input: document.getElementById('input-navbar-icon'), label: document.getElementById('label-navbar-icon') },
    };

    function applyColors() {
        if (!navbarPreview) return;
        if (inputs.bg.input) navbarPreview.style.backgroundColor = inputs.bg.input.value;
        if (inputs.ink.input) navbarPreview.style.color = inputs.ink.input.value;
        if (inputs.icon.input && navbarIcons) navbarIcons.style.color = inputs.icon.input.value;
    }

    Object.entries(inputs).forEach(([, { input, label }]) => {
        if (!input) return;
        input.addEventListener('input', () => {
            label.textContent = input.value;
            applyColors();
        });
    });
})();

// Exposé sur window car appelé depuis les attributs onclick du HTML
window.activateSection = activateSection;