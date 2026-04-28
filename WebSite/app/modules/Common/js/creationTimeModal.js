import ApiClient from './ApiClient.js';

/** ── Constantes partagées (même charte que statistics.js) ────────── */
const COLORS = {
    orange: { bg: 'rgba(255, 159, 64, 0.6)', border: 'rgba(255, 159, 64, 1)' },
    highlight: 'rgba(255, 99, 132, 1)',
};

const POINT = { default: 5, currentUser: 10, hover: 12 };

const LOGARITHMIC_Y_AXIS = {
    beginAtZero: true,
    type: 'logarithmic',
    ticks: {
        stepSize: 1,
        callback: value => Number.isInteger(value) ? value : null,
    },
};

/** ── Helpers (identiques à statistics.js) ────────────────────────── */
const toPoints = data =>
    data.map(({ tranche, count, isHighlighted }) => ({
        x: tranche,
        y: count,
        isHighlighted,
        radius: isHighlighted ? POINT.currentUser : POINT.default,
    }));

const pointColors = (points, defaultColor) =>
    points.map(p => p.isHighlighted ? COLORS.highlight : defaultColor);

/** ── Gestion du graphique ────────────────────────────────────────── */
const api = new ApiClient();
let chartInstance = null;

function destroyChart() {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
}

function renderChart(rawData) {
    const canvas = document.getElementById('creationTimeChart');
    const points = toPoints(rawData);

    chartInstance = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            datasets: [{
                data: points,
                backgroundColor: COLORS.orange.bg,
                borderColor: COLORS.orange.border,
                tension: 0.4,
                showLine: false,
                pointBackgroundColor: pointColors(points, COLORS.orange.border),
                pointRadius: points.map(p => p.radius),
                pointHoverRadius: POINT.hover,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    ...LOGARITHMIC_Y_AXIS,
                    title: { display: true, text: 'Nombre de pages' },
                },
                x: {
                    title: { display: true, text: 'Temps de création (ms)' },
                },
            },
            plugins: { legend: { display: false } },
        },
    });
}

/** ── Éléments DOM ────────────────────────────────────────────────── */
const modal = document.getElementById('creationTimeModal');
const uriLabel = document.getElementById('creationTimeModalUri');
const wrapper = document.getElementById('creationTimeChartWrapper');
const spinner = document.getElementById('creationTimeSpinner');
const errorBox = document.getElementById('creationTimeError');

function setLoading(loading) {
    spinner.classList.toggle('d-none', !loading);
    wrapper.classList.toggle('d-none', loading);
    errorBox.classList.add('d-none');
}

function showError(message) {
    spinner.classList.add('d-none');
    wrapper.classList.add('d-none');
    errorBox.textContent = message;
    errorBox.classList.remove('d-none');
}

async function loadDistribution(uri) {
    destroyChart();
    setLoading(true);

    const endpoint = `/api/visitor-insights/creation-time-distribution?uri=${encodeURIComponent(uri)}`;
    const response = await api.get(endpoint);

    if (response?.success === false) {
        showError(response.error ?? 'Une erreur est survenue.');
        return;
    }

    const data = response?.data ?? response;
    if (!Array.isArray(data) || data.length === 0) {
        showError('Aucune donnée disponible pour cette page.');
        return;
    }

    setLoading(false);
    renderChart(data);
}

document.addEventListener('click', e => {
    const trigger = e.target.closest('.creation-time-trigger');
    if (!trigger) return;

    const uri = trigger.dataset.uri;
    uriLabel.textContent = uri;

    bootstrap.Modal.getOrCreateInstance(modal).show();
    loadDistribution(uri);
});

modal.addEventListener('hidden.bs.modal', destroyChart);