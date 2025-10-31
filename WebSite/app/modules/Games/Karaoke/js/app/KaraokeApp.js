import { AppState } from '../config.js';
import { generateClientId } from '../utils/generateClientId.js';
import { ApiManager } from '../api/ApiManager.js';
import { SyncManager } from '../sync/SyncManager.js';
import { PlaybackScheduler } from '../sync/PlaybackScheduler.js';
import { AudioController } from '../audio/AudioController.js';
import { LyricsRenderer } from '../lyrics/LyricsRenderer.js';
import { UIController } from '../ui/UIController.js';

export class KaraokeApp {
    constructor() {
        this.state = AppState.IDLE;
        this.songId = window.location.pathname.split('/').pop();
        this.clientId = generateClientId();
        this.elements = this.getDOMElements();
        if (!this.elements.audioPlayer || !this.elements.lyricsDisplay) {
            console.error('Critical DOM elements missing:', {
                audioPlayer: !!this.elements.audioPlayer,
                lyricsDisplay: !!this.elements.lyricsDisplay
            });
            throw new Error('Required DOM elements not found');
        }

        this.api = new ApiManager(this.songId, this.clientId);
        this.audio = new AudioController(this.elements.audioPlayer, {
            onTick: (time) => this.handleAudioTick(time),
            onPlay: () => this.handleAudioPlay(),
            onPause: () => this.handleAudioPause(),
            onError: (err) => this.handleError(err),
            onInteractionRequired: () => this.handleInteractionRequired()
        });
        this.lyrics = new LyricsRenderer(this.elements.lyricsDisplay, window.lyricsData || []);
        this.ui = new UIController(this.elements);
        this.sync = new SyncManager(this.api, {
            onActivated: (data) => this.handleSyncActivated(data),
            onDeactivated: () => this.handleSyncDeactivated(),
            onStatusUpdate: (status) => this.handleSyncStatusUpdate(status),
            onError: (err) => this.handleError(err),
            onCriticalError: (msg) => this.handleCriticalError(msg)
        });
        this.scheduler = new PlaybackScheduler(this.sync, () => this.startPlayback());
        this.countdownInterval = null;
        this.sessionActive = false;
    }

    getDOMElements() {
        const els = {
            audioPlayer: document.getElementById('audioPlayer'),
            lyricsDisplay: document.getElementById('lyricsDisplay'),
            playPauseBtn: document.getElementById('playPauseBtn'),
            restartBtn: document.getElementById('restartBtn'),
            progressBar: document.getElementById('progressBar'),
            progressContainer: document.getElementById('progressContainer'),
            currentTimeEl: document.getElementById('currentTime'),
            durationEl: document.getElementById('duration'),
            syncCheckbox: document.getElementById('syncCheckbox'),
            launchForAllBtn: document.getElementById('launchForAllBtn'),
            syncControls: document.getElementById('syncControls'),
            countdownDisplay: document.getElementById('countdownDisplay'),
            connectedClientsEl: document.getElementById('connectedClients'),
            syncStatus: document.getElementById('syncStatus')
        };
        return els;
    }

    async init() {
        try {
            this.lyrics.render();
            this.setupEventListeners();
            await this.checkActiveSyncSession();
            console.log('KaraokeApp initialized', { clientId: this.clientId, songId: this.songId });
        } catch (error) {
            console.error('Init failed:', error);
            this.ui.showError('Échec du chargement');
        }
    }

    setupEventListeners() {
        if (this.elements.audioPlayer) {
            this.elements.audioPlayer.addEventListener('loadedmetadata', () => {
                this.ui.updateDuration(this.audio.getDuration());
            });
            this.elements.audioPlayer.addEventListener('ended', () => {
                this.transitionTo(AppState.IDLE);
            });
        }
        if (this.elements.playPauseBtn) {
            this.elements.playPauseBtn.addEventListener('click', () => this.handlePlayPauseClick());
        }
        if (this.elements.restartBtn) {
            this.elements.restartBtn.addEventListener('click', () => this.handleRestartClick());
        }
        if (this.elements.launchForAllBtn) {
            this.elements.launchForAllBtn.addEventListener('click', () => this.handleLaunchForAllClick());
        }
        if (this.elements.syncCheckbox) {
            this.elements.syncCheckbox.addEventListener('change', (e) => {
                this.handleSyncCheckboxChange(e.target.checked);
            });
        }
        if (this.elements.progressContainer) {
            this.elements.progressContainer.addEventListener('click', (e) => {
                this.handleProgressBarClick(e);
            });
        }
        window.addEventListener('beforeunload', () => this.destroy());
    }

    async checkActiveSyncSession() {
        try {
            const status = await this.api.getStatus();
            if (status.success && status.hasActiveSession) {
                this.elements.syncCheckbox.checked = true;
                await this.handleSyncCheckboxChange(true);
            }
        } catch (error) {
            console.error('Check session error:', error);
        }
    }

    // ========================================================================
    // GESTION D'ÉTAT
    // ========================================================================
    transitionTo(newState) {
        const oldState = this.state;
        console.log(`State: ${oldState} → ${newState}`);
        this.cleanupState(oldState);
        this.state = newState;
        this.setupState(newState);
    }

    cleanupState(state) {
        switch (state) {
            case AppState.SOLO_PLAYING:
                this.audio.stopTick();
                break;
            case AppState.SYNC_COUNTDOWN:
                this.stopCountdown();
                this.scheduler.cancel();
                break;
            case AppState.SYNC_PLAYING:
                this.audio.stopTick();
                break;
        }
    }

    setupState(state) {
        switch (state) {
            case AppState.IDLE:
                this.ui.updatePlayButton(false);
                break;
            case AppState.SOLO_PLAYING:
                this.ui.updatePlayButton(true);
                this.audio.startTick();
                break;
            case AppState.SYNC_WAITING:
                this.ui.showSyncControls();
                break;
            case AppState.SYNC_PLAYING:
                this.audio.startTick();
                this.sync.reducePollingFrequency();
                break;
        }
    }

    // ========================================================================
    // HANDLERS
    // ========================================================================
    handlePlayPauseClick() {
        if ([AppState.SYNC_WAITING, AppState.SYNC_COUNTDOWN, AppState.SYNC_PLAYING].includes(this.state)) return;

        if (this.state === AppState.SOLO_PLAYING) {
            this.audio.pause();
            this.transitionTo(AppState.SOLO_PAUSED);
        } else {
            this.audio.play();
            this.transitionTo(AppState.SOLO_PLAYING);
        }
    }

    handleRestartClick() {
        if ([AppState.SYNC_WAITING, AppState.SYNC_COUNTDOWN, AppState.SYNC_PLAYING].includes(this.state)) return;
        this.audio.seek(0);
        this.lyrics.reset();
    }

    async handleLaunchForAllClick() {
        if (!this.sync.isHost) return;
        this.sessionActive ? await this.stopSession() : await this.startSession();
    }

    async handleSyncCheckboxChange(isChecked) {
        isChecked ? await this.activateSyncMode() : await this.deactivateSyncMode();
    }

    handleProgressBarClick(e) {
        if ([AppState.SYNC_WAITING, AppState.SYNC_COUNTDOWN, AppState.SYNC_PLAYING].includes(this.state)) return;
        const rect = this.elements.progressContainer.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        const newTime = percent * this.audio.getDuration();
        this.audio.seek(newTime);
    }

    handleAudioTick(time) {
        this.lyrics.update(time);
        this.ui.updateProgress(time, this.audio.getDuration());
    }

    handleAudioPlay() { this.ui.updatePlayButton(true); }
    handleAudioPause() { this.ui.updatePlayButton(false); }

    handleSyncActivated({ isHost }) {
        this.transitionTo(AppState.SYNC_WAITING);
    }

    handleSyncDeactivated() {
        this.transitionTo(AppState.IDLE);
        this.ui.showSoloControls();
    }

    handleSyncStatusUpdate(status) {
        this.ui.updateSyncStatus(this.sync.isHost, this.sync.clientsCount);
        if (status.status === 'countdown' && status.playStartTime) {
            this.handleCountdownStatus(status);
        } else {
            this.ui.hideCountdown();
        }
    }

    handleCountdownStatus(status) {
        const remaining = status.playStartTime - this.sync.getServerNow();
        if (remaining > 0) {
            if (this.state !== AppState.SYNC_COUNTDOWN) this.transitionTo(AppState.SYNC_COUNTDOWN);
            this.ui.showCountdown(remaining);
            if (!this.countdownInterval) this.startCountdownDisplay(status.playStartTime);
        } else {
            this.ui.hideCountdown();
            if (this.state !== AppState.SYNC_PLAYING) this.scheduler.schedule(status.playStartTime);
        }
    }

    handleError(error) {
        console.error('App error:', error);
        this.ui.showError(error.message || 'Erreur inconnue');
    }

    handleCriticalError(message) {
        console.error('CRITICAL:', message);
        this.ui.showError(message);
        if (this.elements.syncCheckbox) this.elements.syncCheckbox.checked = false;
        this.deactivateSyncMode();
    }

    handleInteractionRequired() {
        this.ui.showError('Cliquez sur "Lecture" pour activer le son');
    }

    // ========================================================================
    // SESSION
    // ========================================================================
    async activateSyncMode() {
        try {
            this.ui.showSyncControls();
            await this.audio.silentUnlock();
            const result = await this.sync.activate();
            if (!result.success) throw new Error(result.error || 'Sync failed');
            this.transitionTo(AppState.SYNC_WAITING);
        } catch (error) {
            this.handleError(error);
            if (this.elements.syncCheckbox) this.elements.syncCheckbox.checked = false;
            this.ui.showSoloControls();
        }
    }

    async deactivateSyncMode() {
        try {
            await this.sync.deactivate();
            if (this.audio.isPlaying) this.audio.pause();
            this.sessionActive = false;
            this.ui.setLaunchButtonState(false);
            this.ui.showSoloControls();
            this.transitionTo(AppState.IDLE);
        } catch (error) {
            this.handleError(error);
        }
    }

    async startSession() {
        if (!this.sync.isHost) return;
        try {
            this.audio.seek(0);
            this.lyrics.reset();
            this.sessionActive = true;
            this.ui.setLaunchButtonState(true);

            const result = await this.sync.initiateCountdown();
            if (!result.success) throw new Error('Countdown failed');

            this.scheduler.schedule(result.playStartTime);
            this.startCountdownDisplay(result.playStartTime);
            this.transitionTo(AppState.SYNC_COUNTDOWN);
        } catch (error) {
            this.handleError(error);
            this.sessionActive = false;
            this.ui.setLaunchButtonState(false);
        }
    }

    async stopSession() {
        if (!this.sync.isHost) return;
        try {
            this.audio.stop();
            this.lyrics.reset();
            this.stopCountdown();
            this.scheduler.cancel();
            await this.sync.stopPlayback();
            this.sessionActive = false;
            this.ui.setLaunchButtonState(false);
            this.ui.hideCountdown();
            this.transitionTo(AppState.SYNC_WAITING);
        } catch (error) {
            this.handleError(error);
        }
    }

    startPlayback() {
        if (this.audio.isPlaying) return;
        this.audio.play();
        this.transitionTo(AppState.SYNC_PLAYING);
    }

    startCountdownDisplay(targetTime) {
        this.stopCountdown();
        this.countdownInterval = setInterval(() => {
            const remaining = targetTime - this.sync.getServerNow();
            if (remaining <= 0) {
                this.ui.hideCountdown();
                this.stopCountdown();
            } else {
                this.ui.showCountdown(remaining);
            }
        }, 100);
    }

    stopCountdown() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }

    destroy() {
        this.stopCountdown();
        this.scheduler.cancel();
        if (this.sync.isActive) this.sync.deactivate();
        this.audio.destroy();
        this.lyrics.destroy();
        this.api.destroy?.();
    }
}