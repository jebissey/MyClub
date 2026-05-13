/**
 * Gère l'affichage en temps réel du statut de la section « article principal ».
 * Responsabilité unique : lire les inputs article et mettre à jour les éléments DOM correspondants.
 */
export class ArticleStatusManager {
    #inputArticle    = null;
    #inputParagraphs = null;
    #statusEl        = null;
    #previewEl       = null;

    constructor() {
        this.#inputArticle    = document.getElementById('input-featured-article');
        this.#inputParagraphs = document.getElementById('input-featured-paragraphs');
        this.#statusEl        = document.getElementById('article-status');
        this.#previewEl       = document.getElementById('preview-article-content');
    }

    /** Branche les écouteurs d'événements et effectue un premier rendu. */
    init() {
        this.#inputArticle?.addEventListener('input',    () => this.refresh());
        this.#inputParagraphs?.addEventListener('input', () => this.refresh());
        this.refresh();
    }

    /** Recalcule et applique l'affichage selon les valeurs courantes. */
    refresh() {
        const id         = parseInt(this.#inputArticle?.value)    || 0;
        const paragraphs = parseInt(this.#inputParagraphs?.value) || 0;

        const paraLabel = paragraphs === 0
            ? `<span class="text-muted">(article entier)</span>`
            : `<span class="text-muted">(${paragraphs} paragraphe${paragraphs > 1 ? 's' : ''})</span>`;

        if (id > 0) {
            this.#setStatus('alert-success',
                `<i class="bi bi-file-earmark-check me-1"></i>L'article <strong>#${id}</strong> sera affiché en page d'accueil. ${paraLabel}`
            );
            if (this.#previewEl) {
                this.#previewEl.innerHTML =
                    `<div class="d-flex align-items-center gap-2 small text-success fw-semibold">` +
                    `<i class="bi bi-file-earmark-text"></i> Article ID&nbsp;<strong>${id}</strong>&nbsp;${paraLabel}</div>`;
            }
        } else {
            this.#setStatus('alert-info',
                `<i class="bi bi-info-circle me-1"></i>Le dernier article publié sera affiché. ${paraLabel}`
            );
            if (this.#previewEl) {
                this.#previewEl.innerHTML =
                    `<div class="d-flex flex-column gap-1">` +
                    `<div class="rounded-1 bg-secondary-subtle" style="width:80%;height:8px;"></div>` +
                    `<div class="rounded-1 bg-secondary-subtle" style="width:95%;height:8px;"></div>` +
                    `<div class="rounded-1 bg-secondary-subtle" style="width:70%;height:8px;"></div>` +
                    `<span class="fst-italic text-muted d-flex align-items-center gap-1 small mt-1">` +
                    `<i class="bi bi-arrow-return-right"></i>Dernier article&nbsp;${paraLabel}</span></div>`;
            }
        }
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    #setStatus(alertClass, html) {
        if (!this.#statusEl) return;
        this.#statusEl.className = `alert ${alertClass} mb-0`;
        this.#statusEl.innerHTML = html;
    }
}