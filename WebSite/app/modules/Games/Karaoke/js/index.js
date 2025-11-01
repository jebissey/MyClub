import { KaraokeApp } from './app/KaraokeApp.js';

let app;

function initApp() {
    const audioEl = document.getElementById('audioPlayer');
    if (!audioEl || typeof window.lyricsData === 'undefined') {
        console.warn('DOM or lyricsData not ready, retrying...');
        return requestAnimationFrame(initApp);
    }
    try {
        app = new KaraokeApp();
        app.init();
        window.karaokeApp = app;
    } catch (error) {
        alert('Erreur de démarrage. Rechargez la page.');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(initApp);
});