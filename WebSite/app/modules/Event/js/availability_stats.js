import Chart from 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/auto/+esm';
import ChartDataLabels from 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/+esm';

Chart.register(ChartDataLabels);

const data = window.availabilityChartData;

if (!data || !data.labels) {
    console.error('availabilityChartData manquant ou invalide', data);
} else {
    const ctx = document.getElementById('availabilityChart');
    if (!ctx) {
        console.error('Canvas #availabilityChart introuvable');
    } else {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: (window.t && window.t('morning')) || 'Matin',
                        data: data.morning,
                        backgroundColor: 'rgba(54, 162, 235, 0.85)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        stack: 'slots'
                    },
                    {
                        label: (window.t && window.t('afternoon')) || 'Après-midi',
                        data: data.afternoon,
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        stack: 'slots'
                    },
                    {
                        label: (window.t && window.t('evening')) || 'Soir',
                        data: data.evening,
                        backgroundColor: 'rgba(108, 117, 125, 0.85)',
                        borderColor: 'rgba(108, 117, 125, 1)',
                        borderWidth: 1,
                        stack: 'slots'
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
                        stacked: true,
                        beginAtZero: true,
                        display: false,       // pas d'échelle
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.dataset.label}: ${ctx.raw} %`;
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: {
                            weight: 'bold',
                            size: 15
                        },
                        formatter: (value) => {
                            return (value > 0) ? Math.round(value) + ' %' : '';
                        },
                        anchor: 'center',
                        align: 'center',
                        offset: 0,
                        textAlign: 'center',
                        clamp: true,
                        display: true
                    }
                }
            }
        });
    }
}
