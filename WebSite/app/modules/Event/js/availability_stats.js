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
        // Plugin personnalisé : dessine le pourcentage centré sur chaque segment empilé
        const centeredPercentPlugin = {
            id: 'centeredPercentPlugin',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    if (meta.hidden) return;

                    meta.data.forEach((bar, index) => {
                        const value = dataset.data[index];
                        if (!value || value <= 0) return;

                        const { x, y } = bar.getCenterPoint();

                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 15px sans-serif';
                        ctx.fillText(`${Math.round(value)} %`, x, y);
                        ctx.restore();
                    });
                });
            }
        };

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            plugins: [centeredPercentPlugin],
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
                        display: false,
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
                    datalabels: { display: false } // désactivé : remplacé par centeredPercentPlugin
                }
            }
        });
    }
}


const pData = window.participationChartData;

if (pData && pData.labels) {
    const pCtx = document.getElementById('participationChart');
    if (pCtx) {
        const totalsBySlot = {
            morning: pData.morningTotal,
            afternoon: pData.afternoonTotal,
            evening: pData.eveningTotal,
        };
        const countsBySlot = {
            morning: pData.morningCount,
            afternoon: pData.afternoonCount,
            evening: pData.eveningCount,
        };

        // Plugin personnalisé : dessine "moyenne" puis "(total)" centrés sur chaque barre
        const centeredValuePlugin = {
            id: 'centeredValuePlugin',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);
                    if (meta.hidden) return;

                    meta.data.forEach((bar, index) => {
                        const value = dataset.data[index];
                        if (!value || value <= 0) return;

                        const total = totalsBySlot[dataset.slotKey][index];
                        const { x, y } = bar.getCenterPoint();

                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = dataset.slotKey === 'afternoon' ? '#000000' : '#ffffff';

                        ctx.font = 'bold 13px sans-serif';
                        ctx.fillText(String(value), x, y - 7);

                        ctx.font = 'normal 11px sans-serif';
                        ctx.fillText(`(${total})`, x, y + 7);

                        ctx.restore();
                    });
                });
            }
        };

        new Chart(pCtx.getContext('2d'), {
            type: 'bar',
            plugins: [centeredValuePlugin],
            data: {
                labels: pData.labels,
                datasets: [
                    {
                        label: (window.i18n && window.i18n.morning) || 'Matin',
                        data: pData.morningAvg,
                        backgroundColor: 'rgba(54, 162, 235, 0.85)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        slotKey: 'morning'
                    },
                    {
                        label: (window.i18n && window.i18n.afternoon) || 'Après-midi',
                        data: pData.afternoonAvg,
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        slotKey: 'afternoon'
                    },
                    {
                        label: (window.i18n && window.i18n.evening) || 'Soir',
                        data: pData.eveningAvg,
                        backgroundColor: 'rgba(108, 117, 125, 0.85)',
                        borderColor: 'rgba(108, 117, 125, 1)',
                        borderWidth: 1,
                        slotKey: 'evening'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { display: false } }
                },
                plugins: {
                    legend: { position: 'top' },
                    datalabels: { display: false }, // désactivé : remplacé par centeredValuePlugin
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const total = totalsBySlot[ctx.dataset.slotKey][ctx.dataIndex];
                                const count = countsBySlot[ctx.dataset.slotKey][ctx.dataIndex];
                                const avgLabel = (window.i18n && window.i18n.average) || 'Moyenne';
                                const totalLabel = (window.i18n && window.i18n.total) || 'Total';
                                const eventsLabel = (window.i18n && window.i18n.events) || 'Événements';
                                return [
                                    `${ctx.dataset.label}`,
                                    `${avgLabel}: ${ctx.raw}`,
                                    `${totalLabel}: ${total}`,
                                    `${eventsLabel}: ${count}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
}