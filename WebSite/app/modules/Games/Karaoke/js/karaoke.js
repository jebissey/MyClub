const audioPlayer = document.getElementById('audioPlayer');
const lyricsDisplay = document.getElementById('lyricsDisplay');
const playPauseBtn = document.getElementById('playPauseBtn');
const restartBtn = document.getElementById('restartBtn');
const progressBar = document.getElementById('progressBar');
const progressContainer = document.getElementById('progressContainer');
const currentTimeEl = document.getElementById('currentTime');
const durationEl = document.getElementById('duration');

let currentLineIndex = -1;
let isPlaying = false;

// Format time
function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Update duration
audioPlayer.addEventListener('loadedmetadata', () => {
    durationEl.textContent = formatTime(audioPlayer.duration);
});

// Display lyrics
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

    void lyricsDisplay.offsetHeight;

    const container = lyricsDisplay;
    const containerHeight = container.clientHeight;
    const lineHeight = activeLine.offsetHeight;
    const lineOffsetTop = activeLine.offsetTop;

    const lineCenter = lineOffsetTop + lineHeight / 2;
    const containerCenter = containerHeight / 2;
    const targetScrollTop = lineCenter - containerCenter - 150;

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
            if (index === currentLineIndex) {
                line.classList.add('active');
            } else if (index === currentLineIndex + 1) {
                line.classList.add('next');
            }
        });

        // Centrer la ligne active
        scrollToCenterActiveLine();
    }

    // Highlight des mots (inchangé)
    if (currentLineIndex >= 0) {
        const currentLine = lines[currentLineIndex];
        const words = currentLine.querySelectorAll('.word');
        words.forEach(word => {
            const wordTime = parseFloat(word.dataset.time);
            if (currentTime >= wordTime) {
                word.classList.add('highlighted');
            } else {
                word.classList.remove('highlighted');
            }
        });
    }
}

// Play/Pause
playPauseBtn.addEventListener('click', () => {
    if (isPlaying) {
        audioPlayer.pause();
        playPauseBtn.innerHTML = '▶ Lecture';
        isPlaying = false;
    } else {
        audioPlayer.play();
        playPauseBtn.innerHTML = '⏸ Pause';
        isPlaying = true;
    }
});

// Restart
restartBtn.addEventListener('click', () => {
    audioPlayer.currentTime = 0;
    currentLineIndex = -1;
    document.querySelectorAll('.lyric-line').forEach(line => {
        line.classList.remove('active', 'next');
    });
    document.querySelectorAll('.word').forEach(word => {
        word.classList.remove('highlighted');
    });
});

// Update progress
audioPlayer.addEventListener('timeupdate', () => {
    updateLyrics();

    const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
    progressBar.style.width = progress + '%';
    currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
});

// Seek
progressContainer.addEventListener('click', (e) => {
    const rect = progressContainer.getBoundingClientRect();
    const percent = (e.clientX - rect.left) / rect.width;
    audioPlayer.currentTime = percent * audioPlayer.duration;
});

// Auto-pause at end
audioPlayer.addEventListener('ended', () => {
    playPauseBtn.innerHTML = '▶ Lecture';
    isPlaying = false;
});

// Initialize
displayLyrics();

