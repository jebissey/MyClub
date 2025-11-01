import { generateClientId } from '../utils/generateClientId.js';
import { ApiManager } from '../api/ApiManager.js';
import { SyncManager } from '../sync/SyncManager.js';
import { PlaybackScheduler } from '../sync/PlaybackScheduler.js';
import { AudioController } from '../audio/AudioController.js';
import { LyricsRenderer } from '../lyrics/LyricsRenderer.js';
import { UIController } from '../ui/UIController.js';

export class KaraokeApp {
    constructor() {
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
        this.initStateMachine();
    }

    initStateMachine() {
        this.fsm = new StateMachine({
            init: 'idle',
            transitions: [
                // Transitions mode solo
                { name: 'play',           from: 'idle',              to: 'soloPlaying' },
                { name: 'play',           from: 'soloPaused',        to: 'soloPlaying' },
                { name: 'pause',          from: 'soloPlaying',       to: 'soloPaused' },
                { name: 'restart',        from: ['soloPlaying', 'soloPaused'], to: 'idle' },
                
                // Transitions mode sync
                { name: 'activateSync',   from: 'idle',              to: 'syncWaiting' },
                { name: 'activateSync',   from: ['soloPaused', 'soloPlaying'], to: 'syncWaiting' },
                { name: 'deactivateSync', from: ['syncWaiting', 'syncCountdown', 'syncPlaying'], to: 'idle' },
                { name: 'startCountdown', from: 'syncWaiting',       to: 'syncCountdown' },
                { name: 'startPlaying',   from: 'syncCountdown',     to: 'syncPlaying' },
                { name: 'stopSession',    from: ['syncCountdown', 'syncPlaying'], to: 'syncWaiting' },
                { name: 'end',            from: ['soloPlaying', 'syncPlaying'], to: 'idle' }
            ],
            methods: {
                // Hooks appelés lors des transitions
                onLeaveState: (lifecycle) => {
                    console.log(`Quitte l'état: ${lifecycle.from}`);
                    this.cleanupState(lifecycle.from);
                },
                onEnterState: (lifecycle) => {
                    console.log(`Entre dans l'état: ${lifecycle.to}`);
                    this.setupState(lifecycle.to);
                },
                
                // Hooks spécifiques
                onEnterSoloPlaying: () => {
                    this.ui.updatePlayButton(true);
                    this.audio.startTick();
                },
                onLeaveSoloPlaying: () => {
                    this.audio.stopTick();
                },
                
                onEnterSyncWaiting: () => {
                    this.ui.showSyncControls();
                },
                
                onEnterSyncCountdown: () => {
                    // Le countdown display sera géré par handleCountdownStatus
                },
                onLeaveSyncCountdown: () => {
                    this.stopCountdown();
                    this.scheduler.cancel();
                },
                
                onEnterSyncPlaying: () => {
                    this.audio.startTick();
                    this.sync.reducePollingFrequency();
                },
                onLeaveSyncPlaying: () => {
                    this.audio.stopTick();
                },
                
                onEnterIdle: () => {
                    this.ui.updatePlayButton(false);
                },
                
                // Gestion des erreurs de transition
                onInvalidTransition: (transition, from, to) => {
                    console.warn(`Transition invalide: ${transition} de ${from} vers ${to}`);
                }
            }
        });
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
                if (this.fsm.can('end')) {
                    this.fsm.end();
                }
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
    // GESTION D'ÉTAT (nettoyage/setup par état)
    // ========================================================================
    cleanupState(state) {
        // Appelé automatiquement par le FSM lors de onLeaveState
        // Les cleanups spécifiques sont gérés dans les hooks onLeave...
    }

    setupState(state) {
        // Appelé automatiquement par le FSM lors de onEnterState
        // Les setups spécifiques sont gérés dans les hooks onEnter...
    }

    // ========================================================================
    // HANDLERS
    // ========================================================================
    handlePlayPauseClick() {
        // En mode sync, le play/pause n'est pas disponible
        if (this.fsm.is('syncWaiting') || this.fsm.is('syncCountdown') || this.fsm.is('syncPlaying')) {
            return;
        }

        if (this.fsm.is('soloPlaying')) {
            this.audio.pause();
            this.fsm.pause();
        } else if (this.fsm.can('play')) {
            this.audio.play();
            this.fsm.play();
        }
    }

    handleRestartClick() {
        // En mode sync, le restart n'est pas disponible
        if (this.fsm.is('syncWaiting') || this.fsm.is('syncCountdown') || this.fsm.is('syncPlaying')) {
            return;
        }
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
        // En mode sync, la progression n'est pas modifiable
        if (this.fsm.is('syncWaiting') || this.fsm.is('syncCountdown') || this.fsm.is('syncPlaying')) {
            return;
        }
        const rect = this.elements.progressContainer.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        const newTime = percent * this.audio.getDuration();
        this.audio.seek(newTime);
    }

    handleAudioTick(time) {
        this.lyrics.update(time);
        this.ui.updateProgress(time, this.audio.getDuration());
    }

    handleAudioPlay() { 
        this.ui.updatePlayButton(true); 
    }
    
    handleAudioPause() { 
        this.ui.updatePlayButton(false); 
    }

    handleSyncActivated({ isHost }) {
        if (this.fsm.can('activateSync')) {
            this.fsm.activateSync();
        }
    }

    handleSyncDeactivated() {
        if (this.fsm.can('deactivateSync')) {
            this.fsm.deactivateSync();
        }
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
            if (this.fsm.is('syncWaiting') && this.fsm.can('startCountdown')) {
                this.fsm.startCountdown();
            }
            this.ui.showCountdown(remaining);
            if (!this.countdownInterval) {
                this.startCountdownDisplay(status.playStartTime);
            }
        } else {
            this.ui.hideCountdown();
            if (this.fsm.is('syncCountdown') && this.fsm.can('startPlaying')) {
                this.scheduler.schedule(status.playStartTime);
            }
        }
    }

    handleError(error) {
        console.error('App error:', error);
        this.ui.showError(error.message || 'Erreur inconnue');
    }

    handleCriticalError(message) {
        console.error('CRITICAL:', message);
        this.ui.showError(message);
        if (this.elements.syncCheckbox) {
            this.elements.syncCheckbox.checked = false;
        }
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
            if (!result.success) {
                throw new Error(result.error || 'Sync failed');
            }
            
            if (this.fsm.can('activateSync')) {
                this.fsm.activateSync();
            }
        } catch (error) {
            this.handleError(error);
            if (this.elements.syncCheckbox) {
                this.elements.syncCheckbox.checked = false;
            }
            this.ui.showSoloControls();
        }
    }

    async deactivateSyncMode() {
        try {
            await this.sync.deactivate();
            if (this.audio.isPlaying) {
                this.audio.pause();
            }
            this.sessionActive = false;
            this.ui.setLaunchButtonState(false);
            this.ui.showSoloControls();
            
            if (this.fsm.can('deactivateSync')) {
                this.fsm.deactivateSync();
            }
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
            if (!result.success) {
                throw new Error('Countdown failed');
            }

            if (this.fsm.can('startCountdown')) {
                this.fsm.startCountdown();
            }
            
            this.scheduler.schedule(result.playStartTime);
            this.startCountdownDisplay(result.playStartTime);
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
            
            if (this.fsm.can('stopSession')) {
                this.fsm.stopSession();
            }
        } catch (error) {
            this.handleError(error);
        }
    }

    startPlayback() {
        if (this.audio.isPlaying) return;
        
        this.audio.play();
        
        if (this.fsm.can('startPlaying')) {
            this.fsm.startPlaying();
        }
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
        if (this.sync.isActive) {
            this.sync.deactivate();
        }
        this.audio.destroy();
        this.lyrics.destroy();
        this.api.destroy?.();
    }

    // ========================================================================
    // MÉTHODES UTILITAIRES POUR LE FSM
    // ========================================================================
    get currentState() {
        return this.fsm.state;
    }

    canTransition(transition) {
        return this.fsm.can(transition);
    }
}