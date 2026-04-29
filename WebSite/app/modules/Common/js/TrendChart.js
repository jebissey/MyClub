import ChartPanel from './ChartPanel.js';

const TEAL = { bg: 'rgba(75, 192, 192, 0.5)', border: 'rgba(75, 192, 192, 1)' };

export default class TrendChart {
    #panel;
    #instance = null;

    constructor() {
        this.#panel = new ChartPanel(
            'creationTimeTrendWrapper',
            'creationTimeTrendSpinner',
            'creationTimeTrendError',
        );
    }

    destroy() {
        this.#instance?.destroy();
        this.#instance = null;
    }

    setLoading(loading) { this.#panel.setLoading(loading); }
    showError(message)  { this.#panel.showError(message);  }

    render(rawData) {
        const labels   = rawData.map(d => d.label);
        const maxCount = Math.max(...rawData.map(d => d.count), 1);

        const points = rawData
            .map((d, i) => ({
                x:     i,
                y:     d.avgDuration,
                r:     Math.max(4, Math.round(Math.sqrt(d.count / maxCount) * 28)),
                label: d.label,
                count: d.count,
            }))
            .filter(p => p.y !== null && p.y > 0);

        this.#panel.setLoading(false);

        this.#instance = new Chart(
            document.getElementById('creationTimeTrendChart').getContext('2d'),
            {
                type: 'bubble',
                data: {
                    datasets: [{
                        label:           'Durée moy. de création',
                        data:            points,
                        backgroundColor: TEAL.bg,
                        borderColor:     TEAL.border,
                        borderWidth:     1,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: { display: true, text: 'Période' },
                            ticks: {
                                stepSize:    1,
                                maxRotation: 45,
                                minRotation: 0,
                                callback:    (_, i) => labels[i] ?? '',
                            },
                            min: -0.5,
                            max: 11.5,
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Durée moy. (ms)' },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => [
                                    `Période : ${labels[ctx.raw.x] ?? ctx.raw.x}`,
                                    `Durée moy. : ${ctx.raw.y} ms`,
                                    `Pages : ${ctx.raw.count}`,
                                ],
                            },
                        },
                    },
                },
            }
        );
    }
}