import ChartPanel from './ChartPanel.js';

const COLORS = {
    orange:    { bg: 'rgba(255, 159, 64, 0.6)', border: 'rgba(255, 159, 64, 1)' },
    highlight: 'rgba(255, 99, 132, 1)',
};

const POINT = { default: 5, currentUser: 10, hover: 12 };

export default class DistributionChart {
    #panel;
    #instance = null;

    constructor() {
        this.#panel = new ChartPanel(
            'creationTimeChartWrapper',
            'creationTimeSpinner',
            'creationTimeError',
        );
    }

    destroy() {
        this.#instance?.destroy();
        this.#instance = null;
    }

    setLoading(loading) { this.#panel.setLoading(loading); }
    showError(message)  { this.#panel.showError(message);  }

    render(rawData) {
        const points = rawData.map(({ tranche, count, isHighlighted }) => ({
            x: tranche,
            y: count,
            isHighlighted,
            radius: isHighlighted ? POINT.currentUser : POINT.default,
        }));

        this.#panel.setLoading(false);

        this.#instance = new Chart(
            document.getElementById('creationTimeChart').getContext('2d'),
            {
                type: 'line',
                data: {
                    datasets: [{
                        data:                 points,
                        backgroundColor:      COLORS.orange.bg,
                        borderColor:          COLORS.orange.border,
                        tension:              0.4,
                        showLine:             false,
                        pointBackgroundColor: points.map(p =>
                            p.isHighlighted ? COLORS.highlight : COLORS.orange.border
                        ),
                        pointRadius:      points.map(p => p.radius),
                        pointHoverRadius: POINT.hover,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            type: 'logarithmic',
                            ticks: {
                                stepSize: 1,
                                callback: v => Number.isInteger(v) ? v : null,
                            },
                            title: { display: true, text: 'Nombre de pages' },
                        },
                        x: { title: { display: true, text: 'Temps de création (ms)' } },
                    },
                    plugins: { legend: { display: false } },
                },
            }
        );
    }
}