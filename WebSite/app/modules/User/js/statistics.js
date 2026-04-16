const COLORS = {
    green:     { bg: 'rgba(40, 167, 69, 0.6)',  border: 'rgba(40, 167, 69, 1)'  },
    teal:      { bg: 'rgba(75, 192, 192, 0.6)', border: 'rgba(75, 192, 192, 1)' },
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

/**
 * @param {Array<{tranche: string, count: number, isCurrentUser: boolean}>} data
 * @returns {Array<{x: string, y: number, isCurrentUser: boolean, radius: number}>}
 */
const toPoints = data =>
    data.map(({ tranche, count, isCurrentUser }) => ({
        x: tranche,
        y: count,
        isCurrentUser,
        radius: isCurrentUser ? POINT.currentUser : POINT.default,
    }));

/**
 * @param {Array} points
 * @param {string} defaultColor
 * @returns {string[]}
 */
const pointColors = (points, defaultColor) =>
    points.map(p => p.isCurrentUser ? COLORS.highlight : defaultColor);


function createDistributionChart(canvasId, rawData, palette, labels) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) throw new Error(`Canvas not found: #${canvasId}`);

    const points = toPoints(rawData);

    return new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            datasets: [{
                data:                 points,
                backgroundColor:      palette.bg,
                borderColor:          palette.border,
                tension:              0.4,
                showLine:             false,
                pointBackgroundColor: pointColors(points, palette.border),
                pointRadius:          points.map(p => p.radius),
                pointHoverRadius:     POINT.hover,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: { ...LOGARITHMIC_Y_AXIS, title: { display: true, text: labels.y } },
                x: { title: { display: true, text: labels.x } },
            },
            plugins: { legend: { display: false } },
        },
    });
}

function init() {
    createDistributionChart(
        'visitorChart',
        chartData,
        COLORS.teal,
        {
            y: window.t('visitsYAxis'),
            x: window.t('visitsXAxis'),
        },
    );

    createDistributionChart(
        'participationChart',
        participationChartData,
        COLORS.green,
        {
            y: window.t('participationsYAxis'),
            x: window.t('participationsXAxis'),
        },
    );
}

document.addEventListener('DOMContentLoaded', init);