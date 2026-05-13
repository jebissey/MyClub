import { ColorContrastChecker } from './ColorContrastChecker.js';

/**
 * Gère la prévisualisation live des couleurs de la navbar et l'affichage du badge de contraste.
 * Responsabilité unique : synchroniser les color pickers avec le bloc de prévisualisation.
 */
export class NavbarColorManager {
    #els = {};   // Références DOM mises en cache

    constructor() {
        this.#els = {
            preview:  document.getElementById('prev-navbar'),
            icons:    document.getElementById('prev-navbar-icons'),
            contrastInfo: document.getElementById('navbar-contrast-info'),

            bg:       { input: document.getElementById('input-navbar-bg'),   label: document.getElementById('label-navbar-bg')   },
            ink:      { input: document.getElementById('input-navbar-ink'),  label: document.getElementById('label-navbar-ink')  },
            icon:     { input: document.getElementById('input-navbar-icon'), label: document.getElementById('label-navbar-icon') },
        };
    }

    /** Applique les couleurs initiales (issues des data attributes) et branche les écouteurs. */
    init() {
        this.#applyInitialColors();
        this.#bindInputs();
        this.#updateContrast();
    }

    /**
     * Met à jour la prévisualisation (appelé aussi par ColorWheelManager).
     * @param {{ bg?: string, ink?: string, icon?: string }} overrides  Couleurs à forcer.
     */
    applyColors(overrides = {}) {
        const { preview, icons, bg, ink, icon } = this.#els;
        if (!preview) return;

        const bgVal   = overrides.bg   ?? bg.input?.value;
        const inkVal  = overrides.ink  ?? ink.input?.value;
        const iconVal = overrides.icon ?? icon.input?.value;

        if (bgVal)  preview.style.backgroundColor = bgVal;
        if (inkVal) preview.style.color           = inkVal;
        if (iconVal && icons) icons.style.color   = iconVal;

        this.#updateContrast();
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    #applyInitialColors() {
        const { preview, icons } = this.#els;
        if (preview) {
            preview.style.backgroundColor = preview.dataset.bg   ?? '#212529';
            preview.style.color           = preview.dataset.ink  ?? '#ffffff';
        }
        if (icons && preview) {
            icons.style.color = preview.dataset.icon ?? '#ffc107';
        }
    }

    #bindInputs() {
        Object.values(this.#els).forEach(entry => {
            if (!entry?.input) return;
            entry.input.addEventListener('input', () => {
                if (entry.label) entry.label.textContent = entry.input.value;
                this.applyColors();
            });
        });
    }

    #updateContrast() {
        const { contrastInfo, bg, ink } = this.#els;
        if (!contrastInfo || !bg.input || !ink.input) return;

        const ratio             = ColorContrastChecker.ratio(bg.input.value, ink.input.value);
        const { label, cssClass } = ColorContrastChecker.classify(ratio);

        contrastInfo.className  = `small mt-2 fw-semibold ${cssClass}`;
        contrastInfo.innerHTML  =
            `<i class="bi bi-circle-fill me-1"></i>Contraste : ${ratio}:1 — ${label}`;
    }
}