import { t } from '/app/modules/Common/js/i18n.js';
import ApiClient from '/app/modules/Common/js/ApiClient.js';

const api = new ApiClient();

/**
 * Make the Target – front-end
 * All displayed strings come from window.i18n, read through t()
 */
export default class MakeTheTargetManager {
    #config = window.makeTheTargetConfig ?? {};
    #sessionId = this.#config.sessionId ?? 'no-session';

    // Game state
    #target = 0;
    #tiles = [];
    #selectedIds = [];
    #selectedOp = null;
    #history = [];
    #nextId = 100;
    #moves = 0;
    #gameActive = false;
    #foundExact = false;

    // DOM
    #btnDraw = document.getElementById('btn-draw');
    #btnSolution = document.getElementById('btn-solution');
    #btnReset = document.getElementById('btn-reset');
    #targetDisplay = document.getElementById('target-display');
    #tilesCont = document.getElementById('tiles-container');
    #opsCont = document.getElementById('ops-container');
    #historyBox = document.getElementById('history-box');
    #moveCounter = document.getElementById('move-counter');
    #solutionBox = document.getElementById('solution-box');
    #endMessage = document.getElementById('end-message');

    static #SMALL = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    static #LARGE = [25, 50, 75, 100];

    bindEvents() {
        this.#btnDraw?.addEventListener('click', () => this.#drawGame());
        this.#btnSolution?.addEventListener('click', () => this.#onSolution());
        this.#btnReset?.addEventListener('click', () => this.#onReset());
        this.#opsCont?.addEventListener('click', (e) => this.#onOpClick(e));

        this.#drawGame();
    }

    // ── Draw ─────────────────────────────────────────────────────────────────

    #drawGame() {
        const nbLarge = Math.floor(Math.random() * 5);
        const nbSmall = 6 - nbLarge;

        const largeCopy = [...MakeTheTargetManager.#LARGE];
        const smallCopy = [...MakeTheTargetManager.#SMALL];
        this.#shuffle(largeCopy);
        this.#shuffle(smallCopy);

        const drawn = [
            ...largeCopy.slice(0, nbLarge),
            ...smallCopy.slice(0, nbSmall),
        ];
        this.#shuffle(drawn);

        this.#target = Math.floor(Math.random() * 900) + 100;

        this.#tiles = drawn.map((v, i) => ({ id: i + 1, value: v, used: false }));
        this.#selectedIds = [];
        this.#selectedOp = null;
        this.#history = [];
        this.#nextId = 100;
        this.#moves = 0;
        this.#gameActive = true;
        this.#foundExact = false;

        this.#targetDisplay.textContent = this.#target;
        this.#solutionBox.style.display = 'none';
        this.#solutionBox.textContent = '';
        this.#endMessage.style.display = 'none';
        this.#endMessage.innerHTML = '';

        if (this.#btnSolution) this.#btnSolution.disabled = false;
        if (this.#btnReset) this.#btnReset.disabled = false;

        this.#renderTiles();
        this.#renderHistory();
        this.#updateOpsState();
        this.#updateCounter();

        this.#log(`New game drawn – tiles: [${drawn.join(', ')}] target: ${this.#target}`);
    }

    #shuffle(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
    }

    // ── Tiles ────────────────────────────────────────────────────────────────

    #renderTiles() {
        this.#tilesCont.innerHTML = '';

        this.#tiles.forEach(tile => {
            const div = document.createElement('div');
            div.className = 'tile';
            if (tile.used) div.classList.add('used');
            if (this.#selectedIds.includes(tile.id)) div.classList.add('selected');
            if (tile.id >= 100) div.classList.add('result-tile');
            div.dataset.id = tile.id;
            div.textContent = tile.value;

            if (!tile.used && this.#gameActive) {
                div.addEventListener('click', () => this.#onTileClick(tile.id));
            }
            this.#tilesCont.appendChild(div);
        });
    }

    #onTileClick(id) {
        if (!this.#gameActive || this.#foundExact) return;

        const idx = this.#selectedIds.indexOf(id);
        if (idx !== -1) {
            this.#selectedIds.splice(idx, 1);
        } else {
            if (this.#selectedIds.length >= 2) {
                this.#selectedIds.shift();
            }
            this.#selectedIds.push(id);
        }

        this.#selectedOp = null;
        document.querySelectorAll('.op-btn').forEach(b => b.classList.remove('selected'));
        this.#renderTiles();
        this.#updateOpsState();
    }

    // ── Operations ───────────────────────────────────────────────────────────

    #updateOpsState() {
        const canOperate = this.#selectedIds.length === 2 && this.#gameActive && !this.#foundExact;
        document.querySelectorAll('.op-btn').forEach(btn => {
            btn.disabled = !canOperate;
            if (!canOperate) btn.classList.remove('selected');
        });
    }

    #onOpClick(e) {
        const btn = e.target.closest('.op-btn');
        if (!btn || btn.disabled) return;

        this.#selectedOp = btn.dataset.op;
        document.querySelectorAll('.op-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');

        this.#tryCompute();
    }

    #tryCompute() {
        if (this.#selectedIds.length !== 2 || !this.#selectedOp) return;

        const a = this.#tiles.find(tile => tile.id === this.#selectedIds[0]);
        const b = this.#tiles.find(tile => tile.id === this.#selectedIds[1]);
        if (!a || !b || a.used || b.used) return;

        const op = this.#selectedOp;
        const result = this.#applyOp(a.value, b.value, op);
        if (result === null) {
            this.#flashInvalid();
            return;
        }

        a.used = true;
        b.used = true;

        const newTile = { id: this.#nextId++, value: result, used: false };
        this.#tiles.push(newTile);

        this.#history.push({ a: a.value, op, b: b.value, result, id: newTile.id });
        this.#moves++;

        this.#selectedIds = [];
        this.#selectedOp = null;
        document.querySelectorAll('.op-btn').forEach(btn => btn.classList.remove('selected'));

        this.#renderTiles();
        this.#renderHistory();
        this.#updateOpsState();
        this.#updateCounter();

        this.#log(`Operation: ${a.value} ${op} ${b.value} = ${result}`);

        if (result === this.#target) {
            this.#foundExact = true;
            this.#gameActive = false;
            this.#showWin();
        }
    }

    #applyOp(x, y, op) {
        switch (op) {
            case '+': return x + y;
            case '-': return Math.abs(x - y);
            case '*':
                if (x === 1 || y === 1) return null;
                if (x * y >= 2000) return null;
                return x * y;
            case '/': {
                if (x === 1 || y === 1) return null;
                const max = Math.max(x, y);
                const min = Math.min(x, y);
                if (min === 0 || max % min !== 0) return null;
                return max / min;
            }
            default: return null;
        }
    }

    #flashInvalid() {
        document.querySelectorAll('.op-btn').forEach(b => {
            b.style.background = '#ef9a9a';
            setTimeout(() => { b.style.background = ''; }, 300);
        });
    }

    #renderHistory() {
        if (this.#history.length === 0) {
            this.#historyBox.innerHTML = `<em class="text-muted">${t('makethetarget.no_operation_yet')}</em>`;
            return;
        }

        this.#historyBox.innerHTML = this.#history.map(h => {
            const exact = h.result === this.#target ? ' exact' : '';
            const symbol = { '+': '+', '-': '−', '*': '×', '/': '÷' }[h.op];
            return `<div class="history-line${exact}">
                    ${h.a} ${symbol} ${h.b} = <strong>${h.result}</strong>
                </div>`;
        }).join('');
    }

    #updateCounter() {
        const key = this.#moves <= 1 ? 'makethetarget.operation_count' : 'makethetarget.operations_count';
        this.#moveCounter.textContent = t(key).replace('%d', String(this.#moves));
    }

    #showWin() {
        this.#endMessage.style.display = 'block';
        const obtained = t('makethetarget.obtained_in')
            .replace('%1', `<strong>${this.#target}</strong>`)
            .replace('%2', String(this.#moves));

        this.#endMessage.innerHTML = `
        <div class="alert alert-success">
            <h3>🎉 ${t('makethetarget.target_reached')}</h3>
            <p>${obtained}</p>
            <button class="btn btn-primary" onclick="location.reload()">
                ${t('makethetarget.new_game')}
            </button>
        </div>`;

        this.#log(`Game won in ${this.#moves} moves – target ${this.#target}`);
    }

    // ── Solution ─────────────────────────────────────────────────────────────

    #onSolution() {
        if (!this.#gameActive && !this.#foundExact) return;

        const original = this.#tiles
            .filter(tile => tile.id < 100)
            .map(tile => tile.value);

        const solution = this.#findSolution(original, this.#target);

        this.#solutionBox.style.display = 'block';
        if (solution.found) {
            this.#solutionBox.innerHTML =
                `<strong>💡 ${t('makethetarget.solution_found')}</strong>\n\n${solution.steps.join('\n')}`;
        } else {
            this.#solutionBox.innerHTML =
                `<strong>💡 ${t('makethetarget.best_result')}</strong> ${solution.best}\n\n` +
                (solution.steps.length ? solution.steps.join('\n') : t('makethetarget.no_combination'));
        }

        this.#log(`Solution requested – found: ${solution.found}, best: ${solution.best}`);
    }

    #findSolution(startTiles, targetValue) {
        let found = false;
        let bestResult = 0;
        let bestDiff = Infinity;
        let steps = [];

        const calculate = (x, y, op) => {
            switch (op) {
                case 0:
                    if (x !== 1 && y !== 1 && x * y < 2000) return x * y;
                    break;
                case 1:
                    return x + y;
                case 2:
                    if (x !== 1 && y !== 1) {
                        const mx = Math.max(x, y);
                        const mn = Math.min(x, y);
                        if (mn !== 0 && mx % mn === 0) return mx / mn;
                    }
                    break;
                case 3:
                    return Math.abs(x - y);
            }
            return 0;
        };

        const opSymbol = (op) => ['*', '+', '/', '-'][op];

        const search = (tab, path) => {
            if (found) return;

            const n = tab.length;
            for (let i = 0; i < n; i++) {
                for (let j = i + 1; j < n; j++) {
                    for (let op = 0; op < 4 && !found; op++) {
                        const newTile = calculate(tab[i], tab[j], op);
                        if (newTile === 0) continue;

                        const newPath = [
                            ...path,
                            `${Math.max(tab[i], tab[j])} ${opSymbol(op)} ${Math.min(tab[i], tab[j])} = ${newTile}`,
                        ];

                        if (newTile === targetValue) {
                            found = true;
                            steps = newPath;
                            return;
                        }

                        const diff = Math.abs(newTile - targetValue);
                        if (diff < bestDiff) {
                            bestDiff = diff;
                            bestResult = newTile;
                            if (!found) steps = newPath;
                        }

                        if (n > 2) {
                            const newTab = [];
                            for (let k = 0; k < n; k++) {
                                if (k !== i && k !== j) newTab.push(tab[k]);
                            }
                            newTab.push(newTile);
                            search(newTab, newPath);
                        }
                    }
                }
            }
        };

        search([...startTiles], []);
        return { found, best: bestResult, steps };
    }

    // ── Reset ────────────────────────────────────────────────────────────────

    #onReset() {
        const original = this.#tiles
            .filter(tile => tile.id < 100)
            .map(tile => ({ id: tile.id, value: tile.value, used: false }));

        this.#tiles = original;
        this.#selectedIds = [];
        this.#selectedOp = null;
        this.#history = [];
        this.#nextId = 100;
        this.#moves = 0;
        this.#gameActive = true;
        this.#foundExact = false;

        this.#solutionBox.style.display = 'none';
        this.#endMessage.style.display = 'none';

        this.#renderTiles();
        this.#renderHistory();
        this.#updateOpsState();
        this.#updateCounter();

        this.#log('Game reset (same tiles)');
    }

    // ── Logging ──────────────────────────────────────────────────────────────

    async #log(message) {
        try {
            await api.post('/api/makethetarget', {
                message: `Session ${this.#sessionId}: ${message}`,
            });
        } catch (err) {
            console.error('Log error:', err.message ?? err);
        }
    }
}