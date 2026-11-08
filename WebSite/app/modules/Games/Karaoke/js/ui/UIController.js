import { formatTime } from '../utils/formatTime.js';

export class UIController {
    /**
     * @param {Object<string, HTMLElement|null>} elements
     * @param {Object<string, string>} i18n Translated texts provided by the server
     */
    constructor(elements, i18n) {
        this.elements = elements;
        this.i18n = i18n;
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
        this.elements.playPauseBtn.textContent = isPlaying ? this.i18n.pause : this.i18n.play;
        this.elements.playPauseBtn.setAttribute('aria-label', isPlaying ? this.i18n.ariaPause : this.i18n.ariaPlay);
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
        const template = clientsCount > 1 ? this.i18n.clientsOther : this.i18n.clientsOne;
        this.elements.connectedClientsEl.textContent = template.replace('{count}', String(clientsCount));

        if (this.elements.launchForAllBtn && this.elements.syncStatus) {
            this.elements.launchForAllBtn.style.display = isHost ? 'block' : 'none';
            this.elements.syncStatus.style.display = isHost ? 'none' : 'block';
            if (!isHost) {
                this.elements.syncStatus.textContent = this.i18n.waitingForHost;
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
        const button = this.elements.launchForAllBtn;
        if (!button) return;
        if (isActive) {
            button.textContent = this.i18n.stopForAll;
            button.classList.remove('btn-success');
            button.classList.add('btn-danger');
            button.setAttribute('aria-label', this.i18n.ariaStopForAll);
        } else {
            button.textContent = this.i18n.launchForAll;
            button.classList.remove('btn-danger');
            button.classList.add('btn-success');
            button.setAttribute('aria-label', this.i18n.ariaLaunchForAll);
        }
    }

    showError(message) {
        console.error('UI Error:', message);
        if (this.elements.syncStatus) {
            this.elements.syncStatus.textContent = `${this.i18n.errorPrefix} ${message}`;
            this.elements.syncStatus.style.color = 'red';
        }
    }
}