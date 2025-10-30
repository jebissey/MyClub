const audioPlayer = document.getElementById('audioPlayer');
const lyricsDisplay = document.getElementById('lyricsDisplay');
const playPauseBtn = document.getElementById('playPauseBtn');
const restartBtn = document.getElementById('restartBtn');
const progressBar = document.getElementById('progressBar');
const progressContainer = document.getElementById('progressContainer');
const currentTimeEl = document.getElementById('currentTime');
const durationEl = document.getElementById('duration');
const syncCheckbox = document.getElementById('syncCheckbox');
const launchForAllBtn = document.getElementById('launchForAllBtn');
const syncControls = document.getElementById('syncControls');
const countdownDisplay = document.getElementById('countdownDisplay');
const connectedClientsEl = document.getElementById('connectedClients');

let currentLineIndex = -1;
let isPlaying = false;
let isSyncMode = false;
let isHost = false;
let clientId = 'client_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
let syncCheckInterval = null;
let heartbeatInterval = null;
let serverTimeOffset = 0;
let rafId = null;

const songId = window.location.pathname.split('/').pop();
const API_URL = '/api/karaoke';

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

audioPlayer.addEventListener('loadedmetadata', () => {
    durationEl.textContent = formatTime(audioPlayer.duration);
});

function displayLyrics() {
    lyricsDisplay.innerHTML = '';
    lyricsData.forEach((line, index) => {
        const lineDiv = document.createElement('div');
        lineDiv.className = 'lyric-line';
        lineDiv.dataset.index = index;
        lineDiv.dataset.time = line.time;

        if (line.words && line.words.length > 0) {
            line.words.forEach(word => {
                const wordSpan = document.createElement('span');
                wordSpan.className = 'word';
                wordSpan.dataset.time = word.time;
                wordSpan.textContent = word.text + ' ';
                lineDiv.appendChild(wordSpan);
            });
        } else {
            const cleanText = line.text.replace(/<\d+:\d+\.\d+>/g, '');
            lineDiv.textContent = cleanText;
        }

        lyricsDisplay.appendChild(lineDiv);
    });
}

function scrollToCenterActiveLine() {
    if (currentLineIndex < 0) return;
    const activeLine = lyricsDisplay.querySelector('.lyric-line.active');
    if (!activeLine) return;

    const container = lyricsDisplay;
    const containerHeight = container.clientHeight;
    const lineHeight = activeLine.offsetHeight;
    const lineOffsetTop = activeLine.offsetTop;
    const lineCenter = lineOffsetTop + lineHeight / 2;
    const containerCenter = containerHeight / 2;
    const targetScrollTop = lineCenter - containerCenter - 200;

    requestAnimationFrame(() => {
        container.style.scrollBehavior = 'auto';
        container.scrollTop = Math.max(0, targetScrollTop);
        container.style.scrollBehavior = 'smooth';
    });
}

function updateLyrics() {
    const currentTime = audioPlayer.currentTime;
    const lines = lyricsDisplay.querySelectorAll('.lyric-line');
    let newLineIndex = -1;

    for (let i = lyricsData.length - 1; i >= 0; i--) {
        if (currentTime >= lyricsData[i].time) {
            newLineIndex = i;
            break;
        }
    }

    if (newLineIndex !== currentLineIndex) {
        currentLineIndex = newLineIndex;
        lines.forEach((line, index) => {
            line.classList.remove('active', 'next');
            if (index === currentLineIndex) line.classList.add('active');
            else if (index === currentLineIndex + 1) line.classList.add('next');
        });
        scrollToCenterActiveLine();
    }

    if (currentLineIndex >= 0) {
        const words = lines[currentLineIndex]?.querySelectorAll('.word') || [];
        words.forEach(word => {
            const wordTime = parseFloat(word.dataset.time);
            word.classList.toggle('highlighted', currentTime >= wordTime);
        });
    }
}

// Boucle de mise à jour fluide (remplace timeupdate)
function tick() {
    if (isPlaying && !audioPlayer.paused) {
        updateLyrics();

        const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
        progressBar.style.width = progress + '%';
        currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
    }
    rafId = requestAnimationFrame(tick);
}

async function apiCall(action, extraParams = {}) {
    const params = new URLSearchParams({ action, songId, clientId, ...extraParams });
    try {
        const response = await fetch(`${API_URL}?${params}`);
        return await response.json();
    } catch (error) {
        console.error('API call error:', error);
        return { success: false, error: error.message };
    }
}

async function registerClient() { return await apiCall('register'); }
async function sendHeartbeat() { return await apiCall('heartbeat'); }
async function getStatus() { return await apiCall('getStatus'); }
async function startCountdown(data) { return await apiCall('startCountdown', data); }
async function disconnectClient() { return await apiCall('disconnect'); }

async function checkActiveSyncSession() {
    const status = await getStatus();
    if (status.success && status.hasActiveSession) {
        syncCheckbox.checked = true;
        syncCheckbox.dispatchEvent(new Event('change'));
    }
}

syncCheckbox.addEventListener('change', async () => {
    isSyncMode = syncCheckbox.checked;

    if (isSyncMode) {
        playPauseBtn.style.display = 'none';
        restartBtn.style.display = 'none';
        syncControls.style.display = 'flex';

        // Déblocage silencieux du lecteur (une seule fois)
        if (!audioPlayer.dataset.unlocked) {
            audioPlayer.muted = true;
            await audioPlayer.play().catch(() => {});
            audioPlayer.pause();
            audioPlayer.muted = false;
            audioPlayer.dataset.unlocked = 'true';
        }

        const result = await registerClient();
        if (result.success) {
            isHost = result.isHost;
            await updateSyncStatus();
        }
        heartbeatInterval = setInterval(sendHeartbeat, 2000);
        syncCheckInterval = setInterval(updateSyncStatus, 500);
    } else {
        playPauseBtn.style.display = 'inline-block';
        restartBtn.style.display = 'inline-block';
        syncControls.style.display = 'none';

        clearInterval(heartbeatInterval);
        clearInterval(syncCheckInterval);
        heartbeatInterval = null;
        syncCheckInterval = null;
        await disconnectClient();
    }
});

function getServerNow() {
    return (Date.now() / 1000) + serverTimeOffset;
}

async function updateSyncStatus() {
    const status = await getStatus();
    if (!status.success) return;

    if (status.serverTime) {
        serverTimeOffset = status.serverTime - (Date.now() / 1000);
    }

    isHost = status.isHost;
    connectedClientsEl.textContent = `${status.clientsCount} client${status.clientsCount > 1 ? 's' : ''} connecté${status.clientsCount > 1 ? 's' : ''}`;

    const syncStatus = document.getElementById('syncStatus');
    if (isHost) {
        launchForAllBtn.style.display = 'block';
        syncStatus.style.display = 'none';
    } else {
        launchForAllBtn.style.display = 'none';
        syncStatus.style.display = 'block';
        syncStatus.textContent = "En attente du lancement par l'hôte...";
    }

    if (status.status === 'countdown' && status.playStartTime) {
        const serverNow = getServerNow();
        const remaining = status.playStartTime - serverNow;

        if (remaining > 0) {
            countdownDisplay.style.display = 'block';
            countdownDisplay.textContent = Math.ceil(remaining);
        } else {
            countdownDisplay.style.display = 'none';

            // Démarrage précis
            if (!isPlaying) {
                const delayMs = (status.playStartTime - serverNow) * 1000;
                setTimeout(startPlayback, Math.max(0, delayMs));
            }

            // ARRÊT DU POLLING INUTILE
            if (syncCheckInterval) {
                clearInterval(syncCheckInterval);
                syncCheckInterval = null;
            }
            // Optionnel : garder heartbeat pour compteur clients
            // clearInterval(heartbeatInterval); // décommente si tu veux
        }
    } else {
        countdownDisplay.style.display = 'none';
    }
}

function startPlayback() {
    if (isPlaying) return;

    audioPlayer.play().catch(err => console.warn("Lecture bloquée :", err));
    playPauseBtn.innerHTML = 'Pause';
    isPlaying = true;

    // Lancer la boucle de mise à jour
    if (!rafId) {
        rafId = requestAnimationFrame(tick);
    }
}

launchForAllBtn.addEventListener('click', async () => {
    if (!isHost) return;

    audioPlayer.currentTime = 0;
    currentLineIndex = -1;
    document.querySelectorAll('.lyric-line, .word').forEach(el => el.classList.remove('active', 'next', 'highlighted'));

    const delaySeconds = 5;
    const result = await startCountdown({ delay: delaySeconds });
    if (!result.success) return;

    const { playStartTime, serverTime } = result;
    if (serverTime) serverTimeOffset = serverTime - (Date.now() / 1000);

    const delay = (playStartTime - getServerNow()) * 1000;
    if (delay > 0) {
        showCountdown(delay);
        setTimeout(startPlayback, delay);
    } else {
        startPlayback();
    }
});

function showCountdown(delayMs) {
    const end = Date.now() + delayMs;
    countdownDisplay.style.display = 'block';
    const interval = setInterval(() => {
        const remaining = Math.ceil((end - Date.now()) / 1000);
        if (remaining <= 0) {
            clearInterval(interval);
            countdownDisplay.style.display = 'none';
        } else {
            countdownDisplay.textContent = remaining;
        }
    }, 100);
}

playPauseBtn.addEventListener('click', () => {
    if (isSyncMode) return;
    if (isPlaying) {
        audioPlayer.pause();
        playPauseBtn.innerHTML = 'Lecture';
        isPlaying = false;
    } else {
        audioPlayer.play();
        playPauseBtn.innerHTML = 'Pause';
        isPlaying = true;
        if (!rafId) rafId = requestAnimationFrame(tick);
    }
});

restartBtn.addEventListener('click', () => {
    if (isSyncMode) return;
    audioPlayer.currentTime = 0;
    currentLineIndex = -1;
    document.querySelectorAll('.lyric-line').forEach(l => l.classList.remove('active', 'next'));
    document.querySelectorAll('.word').forEach(w => w.classList.remove('highlighted'));
    if (isPlaying) {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(tick);
    }
});

progressContainer.addEventListener('click', (e) => {
    if (isSyncMode) return;
    const rect = progressContainer.getBoundingClientRect();
    const percent = (e.clientX - rect.left) / rect.width;
    audioPlayer.currentTime = percent * audioPlayer.duration;
});

audioPlayer.addEventListener('ended', () => {
    playPauseBtn.innerHTML = 'Lecture';
    isPlaying = false;
    if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
});

window.addEventListener('beforeunload', () => {
    if (isSyncMode) disconnectClient();
});

// Init
displayLyrics();
checkActiveSyncSession();