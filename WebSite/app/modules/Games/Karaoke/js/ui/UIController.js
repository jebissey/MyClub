import { formatTime } from '../utils/formatTime.js';

export class UIController {
    constructor(elements) {
        this.elements = elements;
        this.setupAccessibility();
    }

    setupAccessibility() {
        const buttons = [this.elements.playPauseBtn, this.elements.restartBtn, this.elements.launchForAllBtn];
        buttons.forEach(btn => {
            if (!btn) return;
            btn.setAttribute('role', 'button');
            btn.setAttribute('tabindex', '0');
            btn.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
        });

        if (this.elements.progressContainer) {
            this.elements.progressContainer.setAttribute('role', 'slider');
            this.elements.progressContainer.setAttribute('tabindex', '0');
            this.elements.progressContainer.setAttribute('aria-valuemin', '0');
            this.elements.progressContainer.setAttribute('aria-valuemax', '100');
            this.elements.progressContainer.setAttribute('aria-valuenow', '0');
        }
    }

    updatePlayButton(isPlaying) {
        if (!this.elements.playPauseBtn) return;
        const label = isPlaying ? 'Pause' : 'Lecture';
        this.elements.playPauseBtn.innerHTML = label;
        this.elements.playPauseBtn.setAttribute('aria-label', isPlaying ? 'Mettre en pause' : 'Lancer la lecture');
    }

    updateProgress(currentTime, duration) {
        if (!this.elements.progressBar || !this.elements.currentTimeEl) return;
        const progress = duration > 0 ? (currentTime / duration) * 100 : 0;
        this.elements.progressBar.style.width = `${progress}%`;
        this.elements.currentTimeEl.textContent = formatTime(currentTime);
        if (this.elements.progressContainer) {
            this.elements.progressContainer.setAttribute('aria-valuenow', Math.round(progress));
        }
    }

    updateDuration(duration) {
        if (this.elements.durationEl) {
            this.elements.durationEl.textContent = formatTime(duration);
        }
    }

    showSoloControls() {
        if (this.elements.playPauseBtn) this.elements.playPauseBtn.style.display = 'inline-block';
        if (this.elements.restartBtn) this.elements.restartBtn.style.display = 'inline-block';
        if (this.elements.syncControls) this.elements.syncControls.style.display = 'none';
    }

    showSyncControls() {
        if (this.elements.syncControls) {
            this.elements.syncControls.style.display = 'block';
        }
    }

    updateSyncStatus(isHost, clientsCount) {
        if (!this.elements.connectedClientsEl) return;
        this.elements.connectedClientsEl.textContent = `${clientsCount} client${clientsCount > 1 ? 's' : ''} connecté${clientsCount > 1 ? 's' : ''}`;

        if (this.elements.launchForAllBtn && this.elements.syncStatus) {
            this.elements.launchForAllBtn.style.display = isHost ? 'block' : 'none';
            this.elements.syncStatus.style.display = isHost ? 'none' : 'block';
            if (!isHost) {
                this.elements.syncStatus.textContent = "En attente du lancement par l'hôte...";
            }
        }
    }

    showCountdown(seconds) {
        if (!this.elements.countdownDisplay) return;
        this.elements.countdownDisplay.style.display = 'block';
        this.elements.countdownDisplay.textContent = Math.max(0, Math.ceil(seconds));
        this.elements.countdownDisplay.setAttribute('role', 'timer');
        this.elements.countdownDisplay.setAttribute('aria-live', 'polite');
    }

    hideCountdown() {
        if (this.elements.countdownDisplay) {
            this.elements.countdownDisplay.style.display = 'none';
        }
    }

    setLaunchButtonState(isActive) {
        if (!this.elements.launchForAllBtn) return;
        if (isActive) {
            this.elements.launchForAllBtn.innerHTML = 'Arrêter la lecture pour tous';
            this.elements.launchForAllBtn.classList.remove('btn-success');
            this.elements.launchForAllBtn.classList.add('btn-danger');
            this.elements.launchForAllBtn.setAttribute('aria-label', 'Arrêter la lecture synchronisée');
        } else {
            this.elements.launchForAllBtn.innerHTML = 'Lancer la lecture pour tous';
            this.elements.launchForAllBtn.classList.remove('btn-danger');
            this.elements.launchForAllBtn.classList.add('btn-success');
            this.elements.launchForAllBtn.setAttribute('aria-label', 'Lancer la lecture synchronisée pour tous');
        }
    }

    showError(message) {
        console.error('UI Error:', message);
        if (this.elements.syncStatus) {
            this.elements.syncStatus.textContent = `Erreur: ${message}`;
            this.elements.syncStatus.style.color = 'red';
        }
    }
}