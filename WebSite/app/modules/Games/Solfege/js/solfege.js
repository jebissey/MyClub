/**
 * Solfège learning game: the player presses the piano key that matches the note
 * displayed on the staff. Note names and texts come from window.solfegeConfig.
 */
const config = window.solfegeConfig ?? { noteNames: {}, i18n: {} };
const i18n = config.i18n;

// Natural notes with the vertical position (y) of the note head on the staff
const STAFF_POSITIONS = [
    ['C4', 140], // below the staff (ledger line)
    ['D4', 130], // under the lowest line
    ['E4', 120], // on the lowest line
    ['F4', 110], // between lines
    ['G4', 100], // on a line
    ['A4', 90],  // between lines
    ['B4', 80],  // on a line
];

class SolfegeGame {
    constructor() {
        this.notes = STAFF_POSITIONS.map(([note, y]) => ({
            note,
            name: config.noteNames[note],
            y,
        }));

        this.currentNoteIndex = 0;
        this.attempts = 0;
        this.startTime = null;
        this.scores = [];
        this.totalScore = 0;

        this.init();
    }

    init() {
        this.bindEvents();
        this.startNewNote();
    }

    bindEvents() {
        document.querySelectorAll('.piano-key').forEach(key => {
            key.addEventListener('click', (e) => this.handleKeyPress(e));
        });

        document.getElementById('next-note-btn').addEventListener('click', () => this.nextNote());
        document.getElementById('reset-btn').addEventListener('click', () => this.reset());
    }

    startNewNote() {
        this.attempts = 0;
        this.startTime = Date.now();

        // Reset the key colors
        document.querySelectorAll('.piano-key').forEach(key => {
            key.classList.remove('correct', 'incorrect', 'active');
        });

        this.displayNote();
        this.updateUI();

        document.getElementById('next-note-btn').disabled = true;

        this.updateTimer();
    }

    displayNote() {
        const currentNote = this.notes[this.currentNoteIndex];
        const noteElement = document.querySelector('#current-note circle');

        // Place the note on the staff
        noteElement.setAttribute('cy', currentNote.y);

        // C4 needs an extra ledger line
        const staffLines = document.getElementById('staff-lines');
        const existingExtraLine = document.getElementById('extra-line-c');

        if (currentNote.note === 'C4') {
            if (!existingExtraLine) {
                const extraLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                extraLine.setAttribute('id', 'extra-line-c');
                extraLine.setAttribute('x1', '130');
                extraLine.setAttribute('y1', '140');
                extraLine.setAttribute('x2', '170');
                extraLine.setAttribute('y2', '140');
                extraLine.setAttribute('stroke', '#000');
                extraLine.setAttribute('stroke-width', '1');
                staffLines.appendChild(extraLine);
            }
        } else if (existingExtraLine) {
            existingExtraLine.remove();
        }
    }

    updateTimer() {
        if (this.startTime && document.getElementById('next-note-btn').disabled) {
            const elapsed = (Date.now() - this.startTime) / 1000;
            document.getElementById('timer').textContent = elapsed.toFixed(1) + 's';
            setTimeout(() => this.updateTimer(), 100);
        }
    }

    handleKeyPress(e) {
        if (document.getElementById('next-note-btn').disabled === false) return;

        this.attempts++;
        const keyElement = e.target.closest('.piano-key');
        const pressedNote = keyElement.dataset.note;
        const currentNote = this.notes[this.currentNoteIndex];

        // Visual feedback on the key
        keyElement.classList.add('active');
        setTimeout(() => keyElement.classList.remove('active'), 150);

        if (pressedNote === currentNote.note) {
            const totalTime = Date.now() - this.startTime;

            this.scores.push({
                note: currentNote.note,
                attempts: this.attempts,
                totalTime: totalTime,
            });

            keyElement.classList.add('correct');

            // Fewer mistakes and less time = better score
            const score = Math.max(100 - (this.attempts - 1) * 10 - Math.floor(totalTime / 1000), 10);
            this.totalScore += score;

            document.getElementById('next-note-btn').disabled = false;

            this.showAlert(i18n.correct.replace('{score}', String(score)), 'success');
        } else {
            keyElement.classList.add('incorrect');
            setTimeout(() => keyElement.classList.remove('incorrect'), 500);

            this.showAlert(i18n.tryAgain, 'warning');
        }

        this.updateUI();
    }

    nextNote() {
        this.currentNoteIndex = (this.currentNoteIndex + 1) % this.notes.length;
        this.startNewNote();

        // Save periodically
        if (this.scores.length % 5 === 0) {
            this.saveScores();
        }
    }

    reset() {
        this.currentNoteIndex = 0;
        this.attempts = 0;
        this.scores = [];
        this.totalScore = 0;
        this.startNewNote();
        this.showAlert(i18n.reset, 'info');
    }

    updateUI() {
        const currentNote = this.notes[this.currentNoteIndex];
        document.getElementById('current-note-name').textContent =
            `${currentNote.name} (${currentNote.note})`;
        document.getElementById('attempts').textContent = this.attempts;
        document.getElementById('total-score').textContent = this.totalScore;
    }

    saveScores() {
        if (this.scores.length === 0) return;

        fetch('/solfege/save-score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ scores: this.scores }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showAlert(i18n.scoresSaved, 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    showAlert(message, type) {
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type} alert-dismissible fade show`;
        alertElement.setAttribute('role', 'alert');
        alertElement.textContent = message;

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'alert');
        alertElement.appendChild(closeButton);

        document.getElementById('liveAlertPlaceholder').replaceChildren(alertElement);

        // Auto-dismiss after 3 seconds
        setTimeout(() => alertElement.remove(), 3000);
    }
}

// Start the game when the page is ready
document.addEventListener('DOMContentLoaded', () => {
    new SolfegeGame();
});