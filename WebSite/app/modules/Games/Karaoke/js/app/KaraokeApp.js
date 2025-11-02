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
            onInteractionRequired: () => this.showError('Cliquez sur "Lecture" pour activer le son')
        });
        
        this.lyrics = new LyricsRenderer(this.elements.lyricsDisplay, window.lyricsData || []);
        this.ui = new UIController(this.elements);
        
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
                    // Cleanup si nécessaire
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
            this.showError('Échec du chargement');
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
        // Les boutons solo restent cachés
        // Le bouton "Arrêter pour tous" est visible pour l'hôte
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
            // Préparer l'audio (débloquer autoplay sur mobile)
            await this.audio.silentUnlock();
            
            // Activer le mode sync côté serveur
            const result = await this.sync.activate();
            if (!result.success) {
                throw new Error(result.error || 'Sync activation failed');
            }
            
            // Arrêter la lecture solo si en cours
            if (this.audio.isPlaying) {
                this.audio.pause();
            }
            
            // Transition vers syncReady
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
            // Reset de la lecture
            this.audio.seek(0);
            this.lyrics.reset();
            
            // Demander au serveur d'initier le countdown
            const result = await this.sync.initiateCountdown();
            if (!result.success) {
                throw new Error('Failed to start countdown');
            }
            
            // Transition vers syncSession
            this.fsm.startSync();
            
            // Planifier la lecture
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
    // CALLBACKS SYNC MANAGER
    // ========================================================================
    
    onSyncActivated({ isHost }) {
        console.log(`Sync activated. IsHost: ${isHost}`);
        // La transition FSM est déjà faite dans activateSync()
    }

    onSyncDeactivated() {
        console.log('Sync deactivated');
        // La transition FSM est déjà faite dans deactivateSync()
    }

    onSyncStatusUpdate(status) {
        // Mise à jour de l'UI
        this.ui.updateSyncStatus(this.sync.isHost, this.sync.clientsCount);
        
        // Si on reçoit un statut de countdown depuis le serveur
        if (status.status === 'countdown' && status.playStartTime) {
            this.handleServerCountdown(status);
        } else if (status.status === 'idle') {
            // La session a été arrêtée par l'hôte
            if (this.fsm.is('syncSession') && this.fsm.can('stopSync')) {
                this.fsm.stopSync();
            }
        }
    }

    handleServerCountdown(status) {
        const remaining = status.playStartTime - this.sync.getServerNow();
        
        if (remaining > 0) {
            // Démarrer la session si on n'est pas déjà dedans
            if (this.fsm.is('syncReady') && this.fsm.can('startSync')) {
                this.fsm.startSync();
            }
            
            // Démarrer le countdown visuel
            if (!this.countdownInterval) {
                this.startCountdown(status.playStartTime);
            }
            
            // Planifier la lecture
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
    // LECTURE
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
    // UTILITAIRES
    // ========================================================================
    
    isSoloMode() {
        return this.fsm.is('idle') || this.fsm.is('playing');
    }

    showError(error) {
        const message = error?.message || error || 'Erreur inconnue';
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