import Chart from 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/auto/+esm';

const colorPalette = [
    '#36A2EB', '#FF6384', '#4BC0C0', '#FFCE56', '#9966FF',
    '#FF9F40', '#8BC34A', '#FF5722', '#607D8B', '#009688'
];

const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                boxWidth: 10,
                padding: 10,
                font: { size: 10 }
            }
        }
    }
};

const createPieChart = (id, data) => {
    new Chart(document.getElementById(id), {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                backgroundColor: colorPalette,
                borderWidth: 1
            }]
        },
        options: commonOptions
    });
};

const createBarChart = (id, data) => {
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: window.t('visits'),
                data: data.data,
                backgroundColor: colorPalette[0],
                borderWidth: 1
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: { beginAtZero: true },
                x: { maxRotation: 45, minRotation: 45 }
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const data = window.analyticsData;

    createPieChart('osChart', data.os);
    createPieChart('browserChart', data.browser);
    createBarChart('resolutionChart', data.resolution);

    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: data.type.labels,
            datasets: [{
                data: data.type.data,
                backgroundColor: colorPalette,
                borderWidth: 1
            }]
        },
        options: commonOptions
    });
});