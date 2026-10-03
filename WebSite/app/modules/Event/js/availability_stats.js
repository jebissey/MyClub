import Chart from 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/auto/+esm';
import ChartDataLabels from 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/+esm';
import EventDetailModal from './modules/EventDetailModal.js';

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
        const averagesBySlot = {
            morning: pData.morningAvg,
            afternoon: pData.afternoonAvg,
            evening: pData.eveningAvg,
        };
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

        // 'average' | 'total'
        let mode = document.querySelector('input[name="participationMode"]:checked')?.value ?? 'average';

        const valuesFor = (slotKey) =>
            mode === 'total' ? totalsBySlot[slotKey] : averagesBySlot[slotKey];

        // Valeur principale = celle des barres ; valeur entre parenthèses = l'autre
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

                        const other = mode === 'total'
                            ? averagesBySlot[dataset.slotKey][index]
                            : totalsBySlot[dataset.slotKey][index];
                        const { x, y } = bar.getCenterPoint();

                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = dataset.slotKey === 'afternoon' ? '#000000' : '#ffffff';

                        ctx.font = 'bold 13px sans-serif';
                        ctx.fillText(String(value), x, y - 7);

                        ctx.font = 'normal 11px sans-serif';
                        ctx.fillText(`(${other})`, x, y + 7);

                        ctx.restore();
                    });
                });
            }
        };

        const participationChart = new Chart(pCtx.getContext('2d'), {
            type: 'bar',
            plugins: [centeredValuePlugin],
            data: {
                labels: pData.labels,
                datasets: [
                    {
                        label: (window.i18n && window.i18n.morning) || 'Matin',
                        data: valuesFor('morning'),
                        backgroundColor: 'rgba(54, 162, 235, 0.85)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        slotKey: 'morning'
                    },
                    {
                        label: (window.i18n && window.i18n.afternoon) || 'Après-midi',
                        data: valuesFor('afternoon'),
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        slotKey: 'afternoon'
                    },
                    {
                        label: (window.i18n && window.i18n.evening) || 'Soir',
                        data: valuesFor('evening'),
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
                    datalabels: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const slot = ctx.dataset.slotKey;
                                const avg = averagesBySlot[slot][ctx.dataIndex];
                                const total = totalsBySlot[slot][ctx.dataIndex];
                                const count = countsBySlot[slot][ctx.dataIndex];
                                const avgLabel = (window.i18n && window.i18n.average) || 'Moyenne';
                                const totalLabel = (window.i18n && window.i18n.total) || 'Total';
                                const eventsLabel = (window.i18n && window.i18n.events) || 'Événements';
                                return [
                                    `${ctx.dataset.label}`,
                                    `${avgLabel}: ${avg}`,
                                    `${totalLabel}: ${total}`,
                                    `${eventsLabel}: ${count}`
                                ];
                            }
                        }
                    }
                }
            }
        });

        // --- Toggle moyenne / total ---
        document.querySelectorAll('input[name="participationMode"]').forEach((radio) => {
            radio.addEventListener('change', (e) => {
                if (!e.target.checked) return;
                mode = e.target.value;
                participationChart.data.datasets.forEach((dataset) => {
                    dataset.data = valuesFor(dataset.slotKey);
                });
                participationChart.update();
            });
        });

        // --- Clic sur une barre : ouvre la liste des événements du jour/créneau ---
        const eventDetailModal = new EventDetailModal();
        eventDetailModal.bind();

        pCtx.onclick = (evt) => {
            const points = participationChart.getElementsAtEventForMode(
                evt, 'nearest', { intersect: true }, true
            );
            if (!points.length) return;

            const { datasetIndex, index } = points[0];
            const slotKey = participationChart.data.datasets[datasetIndex].slotKey;
            const dayLabel = pData.labels[index];

            eventDetailModal.open(
                index,
                slotKey,
                dayLabel,
                window.selectedRange || '6m',
                window.selectedStartDate || ''
            );
        };
    }
}