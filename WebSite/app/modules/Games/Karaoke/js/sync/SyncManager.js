import { SYNC_CONFIG } from '../config.js';

export class SyncManager {
    constructor(apiManager, callbacks = {}) {
        this.api = apiManager;
        this.callbacks = callbacks;
        this.serverTimeOffset = 0;
        this.isHost = false;
        this.clientsCount = 0;
        this.heartbeatInterval = null;
        this.statusInterval = null;
        this.isActive = false;
    }

    async activate() {
        if (this.isActive) return { success: true };

        try {
            const result = await this.api.register();
            if (!result.success) throw new Error(result.error || 'Registration failed');

            this.isHost = result.isHost || false;
            this.isActive = true;
            this.startHeartbeat();
            this.startStatusPolling();

            this.callbacks.onActivated?.({ isHost: this.isHost });
            return { success: true, isHost: this.isHost };
        } catch (error) {
            console.error('SyncManager activation failed:', error);
            this.callbacks.onError?.(error);
            return { success: false, error: error.message };
        }
    }

    async deactivate() {
        if (!this.isActive) return;

        this.stopHeartbeat();
        this.stopStatusPolling();

        try { await this.api.disconnect(); } catch (e) { console.error(e); }

        this.isActive = false;
        this.isHost = false;
        this.callbacks.onDeactivated?.();
    }

    startHeartbeat() {
        this.stopHeartbeat();
        this.heartbeatInterval = setInterval(() => this.sendHeartbeat(), SYNC_CONFIG.HEARTBEAT_INTERVAL_MS);
    }

    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }

    startStatusPolling(intervalMs = SYNC_CONFIG.POLL_INTERVAL_MS) {
        this.stopStatusPolling();
        this.statusInterval = setInterval(() => this.updateStatus(), intervalMs);
    }

    stopStatusPolling() {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }
    }

    async sendHeartbeat() {
        try {
            const result = await this.api.sendHeartbeat();
            if (!result.success && result.critical) {
                this.handleCriticalError('Heartbeat failed');
            }
        } catch (error) {
            console.error('Heartbeat error:', error);
        }
    }

    async updateStatus() {
        try {
            const status = await this.api.getStatus();
            if (!status.success) {
                if (status.critical) this.handleCriticalError('Status check failed');
                return;
            }

            if (status.serverTime) {
                this.serverTimeOffset = status.serverTime - (Date.now() / 1000);
            }

            this.isHost = status.isHost || false;
            this.clientsCount = status.clientsCount || 0;
            this.callbacks.onStatusUpdate?.(status);
        } catch (error) {
            console.error('Status update error:', error);
        }
    }

    async initiateCountdown() {
        if (!this.isHost) return { success: false };

        try {
            const result = await this.api.startCountdown(SYNC_CONFIG.COUNTDOWN_DURATION_S);
            if (!result.success) throw new Error(result.error || 'Countdown failed');

            if (result.serverTime) {
                this.serverTimeOffset = result.serverTime - (Date.now() / 1000);
            }

            return { success: true, playStartTime: result.playStartTime };
        } catch (error) {
            console.error('Countdown initiation error:', error);
            this.callbacks.onError?.(error);
            return { success: false, error: error.message };
        }
    }

    async stopPlayback() {
        if (!this.isHost) return { success: false };
        try {
            await this.api.stopSession();
            return { success: true };
        } catch (error) {
            console.error('Stop playback error:', error);
            return { success: false, error: error.message };
        }
    }

    getServerNow() {
        return (Date.now() / 1000) + this.serverTimeOffset;
    }

    handleCriticalError(message) {
        console.error('CRITICAL SYNC ERROR:', message);
        this.callbacks.onCriticalError?.(message);
    }

    reducePollingFrequency() {
        this.startStatusPolling(SYNC_CONFIG.PLAYING_POLL_INTERVAL_MS);
    }
}