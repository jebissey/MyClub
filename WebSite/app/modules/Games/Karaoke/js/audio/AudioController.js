export class AudioController {
    constructor(audioElement, callbacks = {}) {
        this.audio = audioElement;
        this.callbacks = callbacks;
        this.isPlaying = false;
        this.rafId = null;
        this.isUnlocked = false;
        this.lastTick = 0;
    }

    async silentUnlock() {
        if (this.isUnlocked) return;
        try {
            this.audio.muted = true;
            await this.audio.play();
            this.audio.pause();
            this.audio.muted = false;
            this.isUnlocked = true;
            console.log('Audio unlocked silently');
        } catch (error) {
            console.warn('Silent unlock failed:', error);
        }
    }

    async play() {
        try {
            await this.audio.play();
            this.isPlaying = true;
            this.callbacks.onPlay?.();
            this.startTick();
        } catch (error) {
            console.error('Playback error:', error);
            if (error.name === 'NotAllowedError') {
                this.callbacks.onInteractionRequired?.();
            } else {
                this.callbacks.onError?.(error);
            }
        }
    }

    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.callbacks.onPause?.();
        this.stopTick();
    }

    stop() {
        this.pause();
        this.seek(0);
    }

    seek(time) {
        this.audio.currentTime = Math.max(0, Math.min(time, this.audio.duration || 0));
        this.callbacks.onSeek?.(this.audio.currentTime);
    }

    getCurrentTime() { return this.audio.currentTime; }
    getDuration() { return this.audio.duration || 0; }

    startTick() {
        if (this.rafId) return;
        const tick = () => {
            if (this.isPlaying && !this.audio.paused) {
                const now = performance.now();
                if (now - this.lastTick > 16) {
                    this.callbacks.onTick?.(this.getCurrentTime());
                    this.lastTick = now;
                }
                this.rafId = requestAnimationFrame(tick);
            } else {
                this.rafId = null;
            }
        };
        this.lastTick = 0;
        this.rafId = requestAnimationFrame(tick);
    }

    stopTick() {
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
    }

    destroy() {
        this.stopTick();
        this.pause();
    }
}