import { SYNC_CONFIG } from '../config.js';

export class PlaybackScheduler {
    constructor(syncManager, onStart) {
        this.syncManager = syncManager;
        this.onStart = onStart;
        this.scheduledTimeout = null;
        this.rafId = null;
    }

    schedule(targetTime) {
        this.cancel();
        const now = this.syncManager.getServerNow();
        const delay = (targetTime - now) * 1000;

        if (delay > SYNC_CONFIG.SYNC_PRECISION_THRESHOLD_MS) {
            this.scheduledTimeout = setTimeout(
                () => this.schedule(targetTime),
                delay - SYNC_CONFIG.PLAYBACK_BUFFER_MS
            );
        } else {
            const checkStart = () => {
                const remaining = (targetTime - this.syncManager.getServerNow()) * 1000;
                if (remaining <= 0) {
                    this.onStart();
                    this.rafId = null;
                } else {
                    this.rafId = requestAnimationFrame(checkStart);
                }
            };
            this.rafId = requestAnimationFrame(checkStart);
        }
    }

    cancel() {
        if (this.scheduledTimeout) clearTimeout(this.scheduledTimeout);
        if (this.rafId) cancelAnimationFrame(this.rafId);
        this.scheduledTimeout = null;
        this.rafId = null;
    }
}