import { initTinyMCE } from '/app/modules/Common/js/tinymce-config.js';

/**
 * Gère l'activation des sections dans le panneau éditeur.
 * Responsabilité unique : commutation de section + initialisation paresseuse de TinyMCE.
 */
export class SectionManager {
    /** @type {Record<string, {color: string, label: string, hasTiny: boolean}>} */
    static SECTIONS = {
        header:  { color: '#0d6efd', label: 'En-tête',            hasTiny: true  },
        article: { color: '#198754', label: 'Article principal',   hasTiny: false },
        latest:  { color: '#ffc107', label: 'Derniers articles',   hasTiny: false },
        footer:  { color: '#6f42c1', label: 'Pied de page',        hasTiny: true  },
        images:  { color: '#d63384', label: 'Images',              hasTiny: false },
    };

    #activeSection    = null;
    #tinyInitialized  = {};
    #onActivate       = null;   // callback(key) appelé après chaque activation

    /**
     * @param {function(string): void} onActivate  Callback appelé avec la clé de section active.
     */
    constructor(onActivate = null) {
        this.#onActivate = onActivate;
    }

    /**
     * Active une section : met à jour le DOM, initialise TinyMCE si besoin.
     * @param {string} key  Clé de section (ex: 'header', 'article', …)
     */
    activate(key) {
        if (this.#activeSection) {
            this.#deactivate(this.#activeSection);
        }

        this.#activeSection = key;
        const meta = SectionManager.SECTIONS[key];

        document.getElementById('prev-' + key)?.classList.add('active');
        document.getElementById('editor-placeholder')?.classList.add('d-none');

        const editorEl = document.getElementById('editor-' + key);
        if (editorEl) {
            editorEl.classList.remove('d-none');
            editorEl.classList.add(meta.hasTiny ? 'd-flex' : 'd-block');
        }

        this.#setHeader(meta.color, meta.label);
        this.#setNavActive(key);

        if (meta.hasTiny && !this.#tinyInitialized[key]) {
            this.#tinyInitialized[key] = true;
            initTinyMCE('#tinymce-' + key, { mode: 'permissive' });
        }

        this.#onActivate?.(key);
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    #deactivate(key) {
        document.getElementById('prev-' + key)?.classList.remove('active');

        const prevEditor = document.getElementById('editor-' + key);
        if (prevEditor) {
            prevEditor.classList.remove('d-block', 'd-flex');
            prevEditor.classList.add('d-none');
        }

        this.#setNavActive(null, key);
    }

    #setHeader(color, label) {
        const iconEl  = document.getElementById('editor-section-icon');
        const titleEl = document.getElementById('editor-section-title');
        if (iconEl)  iconEl.style.background = color;
        if (titleEl) titleEl.textContent      = label;
    }

    #setNavActive(activeKey, removeKey = null) {
        document.querySelectorAll('.nav-pills .nav-link').forEach(el => {
            const onclick = el.getAttribute('onclick') ?? '';
            if (removeKey && onclick === `activateSection('${removeKey}')`) {
                el.classList.remove('active');
            }
            if (activeKey && onclick === `activateSection('${activeKey}')`) {
                el.classList.add('active');
            }
        });
    }
}