import Chart from 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/auto/+esm';

const chartData = window.chartData;

const chart = new Chart(
    document.getElementById('visitorStatsChart').getContext('2d'),
    {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: window.t('uniqueVisitors'),
                    data: chartData.uniqueVisitors,
                    type: 'line',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderWidth: 2,
                    tension: 0.1,
                    yAxisID: 'y',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    order: 0
                },
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
                legend: { position: 'top' }
            }
        }
    }
);