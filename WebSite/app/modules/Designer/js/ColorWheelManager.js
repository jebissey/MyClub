/**
 * Gère la roue chromatique et les harmonies de couleur.
 * Responsabilité unique : dessin canvas + calcul des harmonies colorées.
 * Délègue l'application des couleurs à un callback pour rester découplé du DOM navbar.
 */
export class ColorWheelManager {
    static #SIZE = 160;

    static #HARMONIES = [
        { key: 'comp',  name: 'Complémentaire', fn: (h, s, l) => [[h, s, l], [(h + 180) % 360, s, Math.min(l + 20, 90)], [(h + 180) % 360, s, Math.max(l - 15, 10)]] },
        { key: 'ana',   name: 'Analogue',        fn: (h, s, l) => [[h, s, l], [(h + 30) % 360, s, l], [(h - 30 + 360) % 360, s, l]] },
        { key: 'tri',   name: 'Triadique',       fn: (h, s, l) => [[h, s, l], [(h + 120) % 360, s, l], [(h + 240) % 360, s, l]] },
        { key: 'split', name: 'Split-comp.',     fn: (h, s, l) => [[h, s, l], [(h + 150) % 360, s, l], [(h + 210) % 360, s, l]] },
        { key: 'sq',    name: 'Carré',           fn: (h, s, l) => [[h, s, l], [(h + 90) % 360, s, l], [(h + 270) % 360, s, l]] },
        { key: 'mono',  name: 'Monochrome',      fn: (h, s, l) => [[h, s, Math.min(l + 30, 90)], [h, s, l], [h, Math.max(s - 30, 10), Math.max(l - 30, 15)]] },
    ];

    /** @type {function(colors: string[]): void} */
    #onHarmonyApplied = null;

    /** @type {function(): string[]}  Retourne [bgHex, inkHex, iconHex] */
    #getColors = null;

    #canvas  = null;
    #ctx     = null;
    #panel   = null;

    /**
     * @param {function(): string[]}           getColors          Lit les 3 couleurs navbar courantes.
     * @param {function(cols: string[]): void} onHarmonyApplied   Applique une harmonie aux couleurs navbar.
     */
    constructor(getColors, onHarmonyApplied) {
        this.#getColors        = getColors;
        this.#onHarmonyApplied = onHarmonyApplied;
    }

    /** Initialise le canvas, les boutons et les écouteurs. */
    init() {
        this.#canvas = document.getElementById('color-wheel');
        this.#panel  = document.getElementById('color-wheel-panel');
        const btnToggle  = document.getElementById('btn-toggle-wheel');
        const iconToggle = document.getElementById('icon-toggle-wheel');

        if (!this.#canvas || !this.#panel) return;

        this.#ctx = this.#canvas.getContext('2d');

        btnToggle?.addEventListener('click', () => {
            this.#panel.classList.toggle('d-none');
            if (iconToggle) {
                iconToggle.className = this.#panel.classList.contains('d-none')
                    ? 'bi bi-chevron-down'
                    : 'bi bi-chevron-up';
            }
            if (!this.#panel.classList.contains('d-none')) this.redraw();
        });

        // Redessine quand une couleur change et que le panneau est ouvert
        ['input-navbar-bg', 'input-navbar-ink', 'input-navbar-icon'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => {
                if (!this.#panel.classList.contains('d-none')) this.redraw();
            });
        });
    }

    /** Force un nouveau rendu complet (roue + points + harmonies). */
    redraw() {
        this.#drawWheel();
        this.#drawDots();
        this.#renderPresets();
    }

    // ── Dessin ─────────────────────────────────────────────────────────────

    #drawWheel() {
        const { SIZE, CX, CY, R } = this.#dims();
        const ctx = this.#ctx;
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

        // Vignette sombre sur le bord
        const dk = ctx.createRadialGradient(CX, CY, R * .55, CX, CY, R);
        dk.addColorStop(0, 'rgba(0,0,0,0)');
        dk.addColorStop(1, 'rgba(0,0,0,.35)');
        ctx.beginPath();
        ctx.arc(CX, CY, R, 0, Math.PI * 2);
        ctx.fillStyle = dk;
        ctx.fill();
    }

    #drawDots() {
        const dots = [
            { id: 'input-navbar-bg',   label: 'BG' },
            { id: 'input-navbar-ink',  label: 'I'  },
            { id: 'input-navbar-icon', label: '★'  },
        ];
        dots.forEach(({ id, label }) => {
            const inp = document.getElementById(id);
            if (!inp) return;

            const [h, s] = ColorWheelManager.#hexToHsl(inp.value);
            const { x, y } = this.#hslToPos(h, s);

            const ctx = this.#ctx;
            ctx.beginPath();
            ctx.arc(x, y, 8, 0, Math.PI * 2);
            ctx.fillStyle   = inp.value;
            ctx.fill();
            ctx.strokeStyle = '#fff';         ctx.lineWidth = 2.5; ctx.stroke();
            ctx.strokeStyle = 'rgba(0,0,0,.4)'; ctx.lineWidth = 1;   ctx.stroke();
            ctx.fillStyle   = 'rgba(0,0,0,.75)';
            ctx.font        = 'bold 8px sans-serif';
            ctx.textAlign   = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, x, y);
        });
    }

    #renderPresets() {
        const [bgHex] = this.#getColors();
        const [h, s, l] = ColorWheelManager.#hexToHsl(bgHex);

        const cont = document.getElementById('harmony-presets');
        if (!cont) return;
        cont.innerHTML = '';

        ColorWheelManager.#HARMONIES.forEach(({ key, name, fn }) => {
            const cols = fn(h, s, l).map(([ch, cs, cl]) => ColorWheelManager.#hslToHex(ch, cs, cl));

            const btn           = document.createElement('button');
            btn.type            = 'button';
            btn.className       = 'btn btn-sm btn-outline-secondary d-flex flex-column align-items-center gap-1 py-2 px-2';
            btn.style.minWidth  = '78px';
            btn.dataset.key     = key;
            btn.innerHTML =
                `<div class="d-flex gap-1">${cols.map(c =>
                    `<span style="width:16px;height:16px;background:${c};border-radius:3px;border:1px solid rgba(0,0,0,.12);display:inline-block;"></span>`
                ).join('')}</div>` +
                `<span style="font-size:.65rem;">${name}</span>`;

            btn.addEventListener('click', () => this.#applyHarmony(cols, key));
            cont.appendChild(btn);
        });
    }

    #applyHarmony(cols, key) {
        document.querySelectorAll('#harmony-presets .btn').forEach(b => b.classList.remove('active'));
        document.querySelector(`#harmony-presets [data-key="${key}"]`)?.classList.add('active');
        this.#onHarmonyApplied(cols);
        this.redraw();
    }

    // ── Helpers géométrie / couleur ────────────────────────────────────────

    #dims() {
        const SIZE = ColorWheelManager.#SIZE;
        return { SIZE, CX: SIZE / 2, CY: SIZE / 2, R: SIZE / 2 - 4 };
    }

    #hslToPos(h, s) {
        const { CX, CY, R } = this.#dims();
        const angle = (h - 90) * Math.PI / 180;
        const dist  = (s / 100) * R;
        return { x: CX + dist * Math.cos(angle), y: CY + dist * Math.sin(angle) };
    }

    /** @returns {[h: number, s: number, l: number]}  Valeurs en degrés/% */
    static #hexToHsl(hex) {
        let r = parseInt(hex.slice(1, 3), 16) / 255,
            g = parseInt(hex.slice(3, 5), 16) / 255,
            b = parseInt(hex.slice(5, 7), 16) / 255;

        const mx = Math.max(r, g, b), mn = Math.min(r, g, b);
        let h, s, l = (mx + mn) / 2;

        if (mx === mn) {
            h = s = 0;
        } else {
            const d = mx - mn;
            s = l > .5 ? d / (2 - mx - mn) : d / (mx + mn);
            switch (mx) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                case g: h = ((b - r) / d + 2)                / 6; break;
                default: h = ((r - g) / d + 4)               / 6;
            }
        }

        return [h * 360, s * 100, l * 100];
    }

    /** @returns {string}  Notation hexadécimale #rrggbb */
    static #hslToHex(h, s, l) {
        s /= 100; l /= 100;
        const a = s * Math.min(l, 1 - l);
        const f = n => {
            const k = (n + h / 30) % 12;
            return l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1));
        };
        return '#' + [f(0), f(8), f(4)]
            .map(x => Math.round(x * 255).toString(16).padStart(2, '0'))
            .join('');
    }
}