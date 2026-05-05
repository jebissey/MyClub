import Chart from 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/auto/+esm';

const chartData = window.chartData;

// ─── Plugin: Min / Avg / Max stock-style overlay ─────────────────────────────
const minMaxPlugin = {
    id: 'minMaxPlugin',

    afterDatasetsDraw(chart) {
        if (!chartData.showMinMaxAvg) return;

        const ctx    = chart.ctx;
        const yScale = chart.scales['y'];
        const xScale = chart.scales['x'];

        const COLOR      = 'rgb(0, 0, 0)';
        const TICK_W     = 10;
        const LINE_W     = 2;
        const DOT_RADIUS = 5;

        chartData.minVisitors.forEach((min, i) => {
            const avg = chartData.avgVisitors[i];
            const max = chartData.maxVisitors[i];

            if (min === null || avg === null || max === null) return;

            const x    = xScale.getPixelForValue(i);
            const yMin = yScale.getPixelForValue(min);
            const yAvg = yScale.getPixelForValue(avg);
            const yMax = yScale.getPixelForValue(max);

            ctx.save();
            ctx.strokeStyle = COLOR;
            ctx.fillStyle   = COLOR;
            ctx.lineWidth   = LINE_W;

            // Vertical line from min to max
            ctx.beginPath();
            ctx.moveTo(x, yMax);
            ctx.lineTo(x, yMin);
            ctx.stroke();

            // Horizontal tick for MAX
            ctx.beginPath();
            ctx.moveTo(x - TICK_W, yMax);
            ctx.lineTo(x + TICK_W, yMax);
            ctx.stroke();

            // Horizontal tick for MIN
            ctx.beginPath();
            ctx.moveTo(x - TICK_W, yMin);
            ctx.lineTo(x + TICK_W, yMin);
            ctx.stroke();

            // Filled dot for AVERAGE
            ctx.beginPath();
            ctx.arc(x, yAvg, DOT_RADIUS, 0, Math.PI * 2);
            ctx.fill();

            ctx.restore();
        });
    }
};

// ─── Ghost dataset for legend ────────────────────────────────────────────────
const minMaxLegendDataset = chartData.showMinMaxAvg ? [{
    label: window.t('minMaxAvg'),
    data: [],
    type: 'line',
    borderColor: 'rgb(0, 0, 0)',
    backgroundColor: 'rgb(0, 0, 0)',
    pointStyle: 'circle',
    pointRadius: 5,
    showLine: false,
    yAxisID: 'y',
    order: -1,
}] : [];

// ─── Chart ───────────────────────────────────────────────────────────────────
new Chart(
    document.getElementById('visitorStatsChart').getContext('2d'),
    {
        type: 'bar',
        plugins: [minMaxPlugin],
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: window.t('uniqueVisitors'),
                    data: chartData.uniqueVisitors,
                    type: 'line',
                    borderColor: 'rgba(54, 162, 235, 0.35)',
                    backgroundColor: 'rgba(54, 162, 235, 0.08)',
                    borderWidth: 1.5,
                    borderDash: [4, 3],
                    tension: 0.1,
                    yAxisID: 'y',
                    pointRadius: 0,
                    fill: true,
                    order: 0
                },
                ...minMaxLegendDataset,
                {
                    label: window.t('s2xx'),
                    data: chartData.views2xx,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                    stack: 'pageViews',
                    order: 1
                },
                {
                    label: window.t('s3xx'),
                    data: chartData.views3xx,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                    stack: 'pageViews',
                    order: 1
                },
                {
                    label: window.t('s4xx'),
                    data: chartData.views4xx,
                    backgroundColor: 'rgba(255, 120, 0, 0.8)',
                    borderColor: 'rgba(255, 120, 0, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                    stack: 'pageViews',
                    order: 1
                },
                {
                    label: window.t('s5xx'),
                    data: chartData.views5xx,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                    stack: 'pageViews',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false }
                },
                y: {
                    position: 'left',
                    title: { display: true, text: window.t('uniqueVisitors') },
                    beginAtZero: true
                },
                y1: {
                    position: 'right',
                    stacked: true,
                    title: { display: true, text: window.t('pageViews') },
                    beginAtZero: true,
                    grid: { drawOnChartArea: false }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        afterBody(items) {
                            if (!chartData.showMinMaxAvg) return [];
                            const i = items[0]?.dataIndex;
                            if (i === undefined) return [];

                            return [
                                `↑ ${window.t('tooltipMax')} : ${chartData.maxVisitors[i]}`,
                                `● ${window.t('tooltipAvg')} : ${chartData.avgVisitors[i]}`,
                                `↓ ${window.t('tooltipMin')} : ${chartData.minVisitors[i]}`
                            ];
                        }
                    }
                }
            }
        }
    }
);