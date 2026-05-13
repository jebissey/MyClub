import { ImageProcessor } from './ImageProcessor.js';

/**
 * Gère l'interface d'upload d'images (file input, drag-and-drop, prévisualisation, feedback).
 * Responsabilité unique : orchestrer l'interaction DOM autour de l'upload d'une image.
 */
export class ImageUploadManager {
    /** Configurations par clé d'image. */
    static #CONFIGS = {
        home: {
            maxW: 48, maxH: 48,
            mode: 'cover',
            mimeType: 'image/png',
            previewStyle: '',
        },
        logo: {
            maxW: 1200, maxH: 1200,
            mode: 'fit',
            mimeType: 'image/png',
            previewStyle: 'max-height:64px;',
        },
        banner: {
            maxW: 1920, maxH: 600,
            mode: 'fit',
            mimeType: 'image/jpeg',
            quality: 0.88,
            previewStyle: 'width:100%;height:64px;object-fit:cover;',
        },
    };

    /**
     * Initialise les gestionnaires d'upload pour les clés spécifiées.
     * @param {string[]} keys  Ex : ['home', 'logo', 'banner']
     */
    static initAll(keys) {
        keys.forEach(key => new ImageUploadManager(key).#bind());
    }

    #key = null;
    #cfg = null;

    constructor(key) {
        this.#key = key;
        this.#cfg = ImageUploadManager.#CONFIGS[key];
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    #bind() {
        const fileInput = document.getElementById('file-'        + this.#key);
        const card      = document.getElementById('upload-card-' + this.#key);
        if (!fileInput || !card) return;

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) this.#process(file);
            fileInput.value = '';   // permet de re-sélectionner le même fichier
        });

        card.addEventListener('dragover', e => {
            e.preventDefault();
            card.classList.add('drag-over');
        });

        card.addEventListener('dragleave', () => card.classList.remove('drag-over'));

        card.addEventListener('drop', e => {
            e.preventDefault();
            card.classList.remove('drag-over');
            const file = e.dataTransfer?.files?.[0];
            if (file?.type.startsWith('image/')) this.#process(file);
        });
    }

    async #process(file) {
        this.#setInfo('pending', `<i class="bi bi-hourglass-split me-1"></i>${window.t('imageProcessing')}`);

        try {
            const result = await ImageProcessor.resize(file, this.#cfg);
            this.#applyResult(result);
        } catch {
            this.#setInfo('error', `<i class="bi bi-exclamation-triangle me-1"></i>${window.t('imageReadError')}`);
        }
    }

    #applyResult({ dataURL, width, height, sizeKB }) {
        const key = this.#key;

        // Prévisualisation dans le panneau éditeur
        const previewEl     = document.getElementById('preview-'     + key);
        const placeholderEl = document.getElementById('placeholder-' + key);
        const dotEl         = document.getElementById('dot-'         + key);

        if (previewEl) {
            previewEl.src        = dataURL;
            previewEl.style.cssText = this.#cfg.previewStyle;
            previewEl.style.display = '';
        }
        if (placeholderEl) placeholderEl.style.display = 'none';
        if (dotEl)         dotEl.style.background      = '#ffc107';   // jaune = modifié, non sauvegardé

        if (key === 'banner') this.#updateBannerThumb(dataURL);

        const hiddenEl = document.getElementById('hidden-' + key);
        if (hiddenEl) hiddenEl.value = dataURL;

        this.#setInfo('ok',
            `<i class="bi bi-check-circle me-1"></i>${width} × ${height} px — ${sizeKB} ko` +
            `<span class="ms-2 text-warning fw-semibold"><i class="bi bi-floppy me-1"></i>${window.t('imageToSave')}</span>`
        );
    }

    /** Met à jour la miniature bannière dans la colonne gauche. */
    #updateBannerThumb(dataURL) {
        const THUMB_STYLE = 'width:100%;height:50px;object-fit:cover;display:block;';
        const thumb       = document.getElementById('prev-thumb-banner');
        if (!thumb) return;

        if (thumb.tagName === 'IMG') {
            thumb.src             = dataURL;
            thumb.style.display   = '';
            thumb.style.cssText   = THUMB_STYLE;
        } else {
            // Était un div placeholder → le remplacer par une vraie img
            const img       = document.createElement('img');
            img.id          = 'prev-thumb-banner';
            img.src         = dataURL;
            img.alt         = '';
            img.style.cssText = THUMB_STYLE;
            thumb.replaceWith(img);
        }
    }

    /**
     * Affiche le badge de statut dans la card.
     * @param {'pending'|'ok'|'error'} state
     * @param {string} html
     */
    #setInfo(state, html) {
        const el = document.getElementById('info-' + this.#key);
        if (!el) return;

        const classMap = { pending: 'alert-info', ok: 'alert-success', error: 'alert-danger' };
        el.className  = `alert ${classMap[state] ?? 'alert-secondary'} py-1 px-2 small mb-0`;
        el.innerHTML  = html;
    }
}