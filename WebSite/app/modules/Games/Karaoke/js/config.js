export const SYNC_CONFIG = Object.freeze({
    POLL_INTERVAL_MS: 500,
    HEARTBEAT_INTERVAL_MS: 2000,
    COUNTDOWN_DURATION_S: 5,
    PLAYBACK_BUFFER_MS: 50,
    PLAYING_POLL_INTERVAL_MS: 10000,
    MAX_RETRY_ATTEMPTS: 3,
    SYNC_PRECISION_THRESHOLD_MS: 100,
    REQUEST_TIMEOUT_MS: 8000
});

export const AppState = Object.freeze({
    IDLE: 'idle',
    SOLO_PLAYING: 'solo_playing',
    SOLO_PAUSED: 'solo_paused',
    SYNC_WAITING: 'sync_waiting',
    SYNC_COUNTDOWN: 'sync_countdown',
    SYNC_PLAYING: 'sync_playing',
    ERROR: 'error'
});