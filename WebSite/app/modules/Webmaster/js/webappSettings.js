import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

// ── Métadonnées des sections ──────────────────────────────────────────────
const SECTIONS = {
    header:  { color: '#0d6efd', label: 'En-tête',          hasTiny: true  },
    article: { color: '#198754', label: 'Article principal', hasTiny: false },
    latest:  { color: '#ffc107', label: 'Derniers articles', hasTiny: false },
    footer:  { color: '#6f42c1', label: 'Pied de page',      hasTiny: true  },
};

let activeSection     = null;
const tinyInitialized = {};

// ── Activation d'une section ──────────────────────────────────────────────
function activateSection(key) {
    if (activeSection) {
        document.getElementById('prev-'    + activeSection)?.classList.remove('active');
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

    document.getElementById('prev-'    + key)?.classList.add('active');
    const editorEl = document.getElementById('editor-' + key);
    if (editorEl) {
        editorEl.classList.remove('d-none');
        editorEl.classList.add(meta.hasTiny ? 'd-flex' : 'd-block');
    }
    document.getElementById('editor-placeholder')?.classList.add('d-none');

    document.getElementById('editor-section-icon').style.background = meta.color;
    document.getElementById('editor-section-title').textContent     = meta.label;

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
    if (key === 'latest')  refreshLatestStatus();
}

// ── Mise à jour live : article principal ─────────────────────────────────
function refreshArticleStatus() {
    const id       = parseInt(document.getElementById('input-featured-article')?.value) || 0;
    const statusEl = document.getElementById('article-status');
    const prevEl   = document.getElementById('preview-article-content');

    if (id > 0) {
        statusEl.className = 'alert alert-success mb-0';
        statusEl.innerHTML = `<i class="bi bi-file-earmark-check me-1"></i>L'article <strong>#${id}</strong> sera affiché en page d'accueil.`;
        if (prevEl) prevEl.innerHTML =
            `<div class="d-flex align-items-center gap-2 small text-success fw-semibold">` +
            `<i class="bi bi-file-earmark-text"></i> Article ID&nbsp;<strong>${id}</strong></div>`;
    } else {
        statusEl.className = 'alert alert-info mb-0';
        statusEl.innerHTML = `<i class="bi bi-info-circle me-1"></i>Le 1<sup>er</sup> paragraphe du dernier article publié (ou celui mis en avant) sera affiché.`;
        if (prevEl) prevEl.innerHTML =
            `<div class="d-flex flex-column gap-1">` +
            `<div class="rounded-1 bg-secondary-subtle" style="width:80%;height:8px;"></div>` +
            `<div class="rounded-1 bg-secondary-subtle" style="width:95%;height:8px;"></div>` +
            `<div class="rounded-1 bg-secondary-subtle" style="width:70%;height:8px;"></div>` +
            `<span class="fst-italic text-muted d-flex align-items-center gap-1 small mt-1">` +
            `<i class="bi bi-arrow-return-right"></i>1<sup>er</sup> paragraphe du dernier article / article mis en avant</span></div>`;
    }
}

// ── Mise à jour live : derniers articles ──────────────────────────────────
function refreshLatestStatus() {
    const count    = parseInt(document.getElementById('input-latest-count')?.value) || 0;
    const statusEl = document.getElementById('latest-count-status');
    const prevEl   = document.getElementById('preview-latest-content');

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

// ── Initialisation au chargement ──────────────────────────────────────────
// type="module" est différé par défaut : le DOM est déjà prêt à ce stade.

document.getElementById('input-featured-article')
    ?.addEventListener('input', refreshArticleStatus);

const countInput  = document.getElementById('input-latest-count');
const countSlider = document.getElementById('slider-latest-count');
if (countInput && countSlider) {
    countInput.addEventListener('input',  () => { countSlider.value = countInput.value;  refreshLatestStatus(); });
    countSlider.addEventListener('input', () => { countInput.value  = countSlider.value; refreshLatestStatus(); });
}

document.getElementById('settingsForm')?.addEventListener('submit', () => {
    if (typeof tinymce !== 'undefined') tinymce.triggerSave();
});

document.getElementById('saveLanguage')?.addEventListener('click', () => {
    const lang    = document.getElementById('languageSelect').value;
    const useLang = document.getElementById('useLanguage').checked ? 1 : 0;
    window.location.href = `/settings-language?lang=${encodeURIComponent(lang)}&use_language=${useLang}`;
});

// Exposé sur window car appelé depuis les attributs onclick du HTML
window.activateSection = activateSection;