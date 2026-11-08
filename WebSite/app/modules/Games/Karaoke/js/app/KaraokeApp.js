import { ApiManager } from '../api/ApiManager.js';
import { SyncManager } from '../sync/SyncManager.js';
import { PlaybackScheduler } from '../sync/PlaybackScheduler.js';
import { AudioController } from '../audio/AudioController.js';
import { LyricsRenderer } from '../lyrics/LyricsRenderer.js';
import { UIController } from '../ui/UIController.js';

export class KaraokeApp {
    constructor() {
        this.songId = window.location.pathname.split('/').pop();
        this.clientId = window.sessionId ?? 'no-session';
        this.i18n = window.karaokeI18n ?? {};
        this.elements = this.getDOMElements();

        this.validateDOMElements();
        this.initializeManagers();
        this.initStateMachine();

        this.countdownInterval = null;
    }

    validateDOMElements() {
        if (!this.elements.audioPlayer || !this.elements.lyricsDisplay) {
            throw new Error('Required DOM elements not found');
        }
    }

    initializeManagers() {
        this.api = new ApiManager(this.songId, this.clientId);

        this.audio = new AudioController(this.elements.audioPlayer, {
            onTick: (time) => this.updatePlaybackPosition(time),
            onPlay: () => this.ui.updatePlayButton(true),
            onPause: () => this.ui.updatePlayButton(false),
            onEnded: () => this.handlePlaybackEnded(),
            onError: (err) => this.showError(err),
            onInteractionRequired: () => this.showError(this.i18n.clickToPlay)
        });

        this.lyrics = new LyricsRenderer(this.elements.lyricsDisplay, window.lyricsData || []);
        this.ui = new UIController(this.elements, this.i18n);

        this.sync = new SyncManager(this.api, {
            onActivated: (data) => this.onSyncActivated(data),
            onDeactivated: () => this.onSyncDeactivated(),
            onStatusUpdate: (status) => this.onSyncStatusUpdate(status),
            onError: (err) => this.showError(err),
            onCriticalError: (msg) => this.onCriticalSyncError(msg)
        });

        this.scheduler = new PlaybackScheduler(this.sync, () => this.startSyncPlayback());
    }

    initStateMachine() {
        this.fsm = new StateMachine({
            init: 'idle',
            transitions: [
                { name: 'play',        from: 'idle',        to: 'playing' },
                { name: 'pause',       from: 'playing',     to: 'idle' },
                { name: 'ended',       from: 'playing',     to: 'idle' },

                { name: 'enableSync',  from: ['idle', 'playing'], to: 'syncReady' },
                { name: 'disableSync', from: ['syncReady', 'syncSession'], to: 'idle' },
                { name: 'startSync',   from: 'syncReady',   to: 'syncSession' },
                { name: 'stopSync',    from: 'syncSession', to: 'syncReady' },
                { name: 'endSync',     from: 'syncSession', to: 'syncReady' }
            ],
            methods: {
                onEnterPlaying: () => {
                    this.audio.startTick();
                    this.ui.updatePlayButton(true);
                },
                onLeavePlaying: () => {
                    this.audio.stopTick();
                },

                onEnterSyncReady: () => {
                    this.showSyncReadyUI();
                },
                onLeaveSyncReady: () => {
                    // Cleanup if needed
                },

                onEnterSyncSession: () => {
                    this.audio.startTick();
                    this.sync.reducePollingFrequency();
                    this.showSyncSessionUI();
                },
                onLeaveSyncSession: () => {
                    this.audio.stopTick();
                    this.clearCountdown();
                    this.scheduler.cancel();
                },

                onEnterIdle: () => {
                    this.ui.updatePlayButton(false);
                    this.showSoloUI();
                },

                onInvalidTransition: (t, from) => {
                    console.warn(`Invalid transition: ${t} from ${from}`);
                }
            }
        });
    }

    getDOMElements() {
        return {
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
    }

    async init() {
        try {
            this.lyrics.render();
            this.setupEventListeners();
            await this.checkForActiveSync();
        } catch (error) {
            console.error('Init failed:', error);
            this.showError(this.i18n.loadFailed);
        }
    }

    setupEventListeners() {
        this.elements.audioPlayer?.addEventListener('loadedmetadata', () => {
            this.ui.updateDuration(this.audio.getDuration());
        });

        this.elements.playPauseBtn?.addEventListener('click', () => this.togglePlayPause());
        this.elements.restartBtn?.addEventListener('click', () => this.restart());
        this.elements.launchForAllBtn?.addEventListener('click', () => this.toggleSyncSession());
        this.elements.syncCheckbox?.addEventListener('change', (e) => this.toggleSyncMode(e.target.checked));
        this.elements.progressContainer?.addEventListener('click', (e) => this.seekFromClick(e));

        window.addEventListener('beforeunload', () => this.destroy());
    }

    async checkForActiveSync() {
        try {
            const status = await this.api.getStatus();
            if (status.success && status.hasActiveSession) {
                this.elements.syncCheckbox.checked = true;
                await this.toggleSyncMode(true);
            }
        } catch (error) {
            console.error('Check session error:', error);
        }
    }

    showSoloUI() {
        this.ui.showSoloControls();
        this.elements.playPauseBtn?.classList.remove('hidden');
        this.elements.restartBtn?.classList.remove('hidden');
    }

    showSyncReadyUI() {
        this.ui.showSyncControls();
        this.elements.playPauseBtn?.classList.add('hidden');
        this.elements.restartBtn?.classList.add('hidden');

        if (this.sync.isHost) {
            this.elements.launchForAllBtn?.classList.remove('hidden');
            this.ui.setLaunchButtonState(false);
        }
    }

    showSyncSessionUI() {
        // Solo buttons stay hidden.
        // The "stop for everyone" button is visible for the host.
        if (this.sync.isHost) {
            this.ui.setLaunchButtonState(true);
        }
    }

    togglePlayPause() {
        if (!this.isSoloMode()) return;

        if (this.fsm.is('playing')) {
            this.audio.pause();
            this.fsm.pause();
        } else if (this.fsm.can('play')) {
            this.audio.play();
            this.fsm.play();
        }
    }

    restart() {
        if (!this.isSoloMode()) return;

        this.audio.seek(0);
        this.lyrics.reset();
    }

    seekFromClick(e) {
        if (!this.isSoloMode()) return;

        const rect = this.elements.progressContainer.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        const newTime = percent * this.audio.getDuration();
        this.audio.seek(newTime);
    }

    async toggleSyncMode(enable) {
        if (enable) {
            await this.activateSync();
        } else {
            await this.deactivateSync();
        }
    }

    async activateSync() {
        try {
            // Prepare the audio (unlock autoplay on mobile)
            await this.audio.silentUnlock();

            // Enable sync mode on the server
            const result = await this.sync.activate();
            if (!result.success) {
                throw new Error(result.error || this.i18n.syncActivationFailed);
            }

            // Stop solo playback if running
            if (this.audio.isPlaying) {
                this.audio.pause();
            }

            // Move to syncReady
            if (this.fsm.can('enableSync')) {
                this.fsm.enableSync();
            }

        } catch (error) {
            this.showError(error);
            this.elements.syncCheckbox.checked = false;
        }
    }

    async deactivateSync() {
        try {
            await this.sync.deactivate();

            if (this.audio.isPlaying) {
                this.audio.pause();
            }

            if (this.fsm.can('disableSync')) {
                this.fsm.disableSync();
            }

        } catch (error) {
            this.showError(error);
        }
    }

    async toggleSyncSession() {
        if (!this.sync.isHost) return;

        if (this.fsm.is('syncSession')) {
            await this.stopSyncSession();
        } else if (this.fsm.is('syncReady')) {
            await this.startSyncSession();
        }
    }

    async startSyncSession() {
        if (!this.sync.isHost || !this.fsm.can('startSync')) return;

        try {
            // Reset playback
            this.audio.seek(0);
            this.lyrics.reset();

            // Ask the server to initiate the countdown
            const result = await this.sync.initiateCountdown();
            if (!result.success) {
                throw new Error(this.i18n.countdownFailed);
            }

            // Move to syncSession
            this.fsm.startSync();

            // Schedule playback
            this.scheduler.schedule(result.playStartTime);
            this.startCountdown(result.playStartTime);

        } catch (error) {
            this.showError(error);
        }
    }

    async stopSyncSession() {
        if (!this.sync.isHost || !this.fsm.can('stopSync')) return;

        try {
            this.audio.stop();
            this.lyrics.reset();

            await this.sync.stopPlayback();

            this.fsm.stopSync();

        } catch (error) {
            this.showError(error);
        }
    }

    // ========================================================================
    // SYNC MANAGER CALLBACKS
    // ========================================================================

    onSyncActivated({ isHost }) {
        console.log(`Sync activated. IsHost: ${isHost}`);
        // The FSM transition is already done in activateSync()
    }

    onSyncDeactivated() {
        console.log('Sync deactivated');
        // The FSM transition is already done in deactivateSync()
    }

    onSyncStatusUpdate(status) {
        // Update the UI
        this.ui.updateSyncStatus(this.sync.isHost, this.sync.clientsCount);

        // If the server reports a countdown
        if (status.status === 'countdown' && status.playStartTime) {
            this.handleServerCountdown(status);
        } else if (status.status === 'idle') {
            // The session was stopped by the host
            if (this.fsm.is('syncSession') && this.fsm.can('stopSync')) {
                this.fsm.stopSync();
            }
        }
    }

    handleServerCountdown(status) {
        const remaining = status.playStartTime - this.sync.getServerNow();

        if (remaining > 0) {
            // Join the session if not already in it
            if (this.fsm.is('syncReady') && this.fsm.can('startSync')) {
                this.fsm.startSync();
            }

            // Start the visual countdown
            if (!this.countdownInterval) {
                this.startCountdown(status.playStartTime);
            }

            // Schedule playback
            this.scheduler.schedule(status.playStartTime);
        }
    }

    onCriticalSyncError(message) {
        console.error('CRITICAL SYNC ERROR:', message);
        this.showError(message);
        this.elements.syncCheckbox.checked = false;
        this.deactivateSync();
    }

    // ========================================================================
    // COUNTDOWN
    // ========================================================================

    startCountdown(targetTime) {
        this.clearCountdown();

        this.countdownInterval = setInterval(() => {
            const remaining = targetTime - this.sync.getServerNow();

            if (remaining <= 0) {
                this.ui.hideCountdown();
                this.clearCountdown();
            } else {
                this.ui.showCountdown(remaining);
            }
        }, 100);
    }

    clearCountdown() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
        this.ui.hideCountdown();
    }

    // ========================================================================
    // PLAYBACK
    // ========================================================================

    startSyncPlayback() {
        if (this.audio.isPlaying) return;
        this.audio.play();
    }

    updatePlaybackPosition(time) {
        this.lyrics.update(time);
        this.ui.updateProgress(time, this.audio.getDuration());
    }

    handlePlaybackEnded() {
        if (this.fsm.is('playing')) {
            this.fsm.ended();
        } else if (this.fsm.is('syncSession')) {
            this.fsm.endSync();
        }
    }

    // ========================================================================
    // UTILITIES
    // ========================================================================

    isSoloMode() {
        return this.fsm.is('idle') || this.fsm.is('playing');
    }

    showError(error) {
        const message = error?.message || error || this.i18n.unknownError;
        console.error('App error:', message);
        this.ui.showError(message);
    }

    destroy() {
        this.clearCountdown();
        this.scheduler.cancel();

        if (this.sync.isActive) {
            this.sync.deactivate();
        }

        this.audio.destroy();
        this.lyrics.destroy();
        this.api.destroy?.();
    }
}