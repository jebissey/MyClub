document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('visitorChart').getContext('2d');
    const dataPoints = chartData.map(item => ({
        x: item.tranche,
        y: item.count,
        radius: item.isCurrentUser ? 10 : 5
    }));

    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Participants par tranche',
                data: dataPoints,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.4,
                pointBackgroundColor: dataPoints.map(p => p.isCurrentUser ? 'rgba(255, 99, 132, 1)' : 'rgba(75, 192, 192, 1)'),
                pointRadius: dataPoints.map(p => p.radius),
                pointHoverRadius: 12,
                showLine: false
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function (value) {
                            return Number.isInteger(value) ? value : null;
                        }
                    },
                    type: 'logarithmic',
                    title: {
                        display: true,
                        text: 'Visiteurs'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Nombre de pages visitées'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            maintainAspectRatio: false
        }
    });
});
