export class LyricsRenderer {
    constructor(container, data) {
        this.container = container;
        this.data = this.validateData(data);
        this.currentLineIndex = -1;
        this.cachedLines = null;
        this.scrollPending = false;
    }

    validateData(data) {
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn('Invalid lyrics data');
            return [];
        }
        return data;
    }

    render() {
        if (this.data.length === 0) {
            this.container.innerHTML = '<p class="error">Paroles non disponibles</p>';
            return;
        }

        this.container.innerHTML = '';
        this.data.forEach((line, index) => {
            const lineDiv = document.createElement('div');
            lineDiv.className = 'lyric-line';
            lineDiv.dataset.index = index;
            lineDiv.dataset.time = line.time;
            lineDiv.setAttribute('aria-live', 'polite');

            if (line.words?.length > 0) {
                line.words.forEach(word => {
                    const span = document.createElement('span');
                    span.className = 'word';
                    span.dataset.time = word.time;
                    span.textContent = word.text + ' ';
                    lineDiv.appendChild(span);
                });
            } else {
                const clean = (line.text || '').replace(/<\d+:\d+\.\d+>/g, '');
                lineDiv.textContent = clean;
            }
            this.container.appendChild(lineDiv);
        });

        this.cachedLines = Array.from(this.container.querySelectorAll('.lyric-line'));
    }

    findLineIndex(time) {
        let low = 0, high = this.data.length;
        while (low < high) {
            const mid = (low + high) >>> 1;
            if (this.data[mid].time <= time) {
                low = mid + 1;
            } else {
                high = mid;
            }
        }
        return low - 1;
    }

    update(currentTime) {
        if (this.data.length === 0 || !this.cachedLines) return;

        const newLineIndex = this.findLineIndex(currentTime);
        if (newLineIndex !== this.currentLineIndex) {
            this.currentLineIndex = newLineIndex;
            this.updateLineClasses();
            this.scrollToActive();
        }
        this.updateWords(currentTime);
    }

    updateLineClasses() {
        this.cachedLines.forEach((line, i) => {
            line.classList.toggle('active', i === this.currentLineIndex);
            line.classList.toggle('next', i === this.currentLineIndex + 1);
            const isActive = i === this.currentLineIndex;
            if (isActive) line.setAttribute('aria-current', 'true');
            else line.removeAttribute('aria-current');
        });
    }

    updateWords(currentTime) {
        if (this.currentLineIndex < 0) return;
        const line = this.cachedLines[this.currentLineIndex];
        if (!line) return;

        line.querySelectorAll('.word').forEach(word => {
            const t = parseFloat(word.dataset.time);
            if (!isNaN(t)) {
                word.classList.toggle('highlighted', currentTime >= t);
            }
        });
    }

    scrollToActive() {
        if (this.scrollPending || this.currentLineIndex < 0) return;
        this.scrollPending = true;

        requestAnimationFrame(() => {
            const active = this.cachedLines[this.currentLineIndex];
            if (!active) { this.scrollPending = false; return; }

            const containerH = this.container.clientHeight;
            const lineH = active.offsetHeight;
            const lineTop = active.offsetTop;
            const target = lineTop + lineH / 2 - containerH / 2 - 220;

            this.container.style.scrollBehavior = 'auto';
            this.container.scrollTop = Math.max(0, target);

            requestAnimationFrame(() => {
                this.container.style.scrollBehavior = 'smooth';
                this.scrollPending = false;
            });
        });
    }

    reset() {
        this.currentLineIndex = -1;
        this.cachedLines?.forEach(line => {
            line.classList.remove('active', 'next');
            line.removeAttribute('aria-current');
        });
        this.container.querySelectorAll('.word').forEach(w => w.classList.remove('highlighted'));
        this.container.scrollTop = 0;
    }

    destroy() {
        this.container.innerHTML = '';
        this.cachedLines = null;
    }
}