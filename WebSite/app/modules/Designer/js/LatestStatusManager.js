/**
 * Gère l'affichage en temps réel du statut de la section « derniers articles ».
 * Responsabilité unique : synchroniser input + slider et mettre à jour les éléments DOM.
 */
export class LatestStatusManager {
    #inputCount  = null;
    #slider      = null;
    #statusEl    = null;
    #previewEl   = null;

    static #MAX_PREVIEW_ROWS = 6;

    constructor() {
        this.#inputCount = document.getElementById('input-latest-count');
        this.#slider     = document.getElementById('slider-latest-count');
        this.#statusEl   = document.getElementById('latest-count-status');
        this.#previewEl  = document.getElementById('preview-latest-content');
    }

    /** Branche les écouteurs d'événements et effectue un premier rendu. */
    init() {
        this.#inputCount?.addEventListener('input', () => {
            if (this.#slider) this.#slider.value = this.#inputCount.value;
            this.refresh();
        });

        this.#slider?.addEventListener('input', () => {
            if (this.#inputCount) this.#inputCount.value = this.#slider.value;
            this.refresh();
        });

        this.refresh();
    }

    /** Recalcule et applique l'affichage selon la valeur courante. */
    refresh() {
        const count = parseInt(this.#inputCount?.value) || 0;

        this.#renderStatus(count);
        this.#renderPreview(count);
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    #renderStatus(count) {
        if (!this.#statusEl) return;

        if (count === 0) {
            this.#statusEl.className = 'alert alert-warning mb-0';
            this.#statusEl.innerHTML =
                `<i class="bi bi-eye-slash me-1"></i>La section « derniers articles » <strong>ne sera pas affichée</strong>.`;
        } else {
            this.#statusEl.className = 'alert alert-success mb-0';
            this.#statusEl.innerHTML =
                `<i class="bi bi-check-circle me-1"></i>Les <strong>${count}</strong> derniers articles seront listés.`;
        }
    }

    #renderPreview(count) {
        if (!this.#previewEl) return;

        if (count === 0) {
            this.#previewEl.innerHTML =
                `<span class="fst-italic text-muted d-flex align-items-center gap-1 small">` +
                `<i class="bi bi-eye-slash"></i> Section masquée</span>`;
            return;
        }

        const shown = Math.min(count, LatestStatusManager.#MAX_PREVIEW_ROWS);
        let html = '';

        for (let i = 0; i < shown; i++) {
            html +=
                `<div class="d-flex justify-content-between align-items-center py-1 border-bottom">` +
                `<div class="rounded-1 bg-secondary-subtle" style="width:65%;height:8px;"></div>` +
                `<span class="badge bg-secondary rounded-pill" style="font-size:.6rem;">01/01</span></div>`;
        }

        if (count > LatestStatusManager.#MAX_PREVIEW_ROWS) {
            html += `<div class="text-center text-muted mt-1" style="font-size:.62rem;">+ ${count - LatestStatusManager.#MAX_PREVIEW_ROWS} autres…</div>`;
        }

        this.#previewEl.innerHTML = html;
    }
}