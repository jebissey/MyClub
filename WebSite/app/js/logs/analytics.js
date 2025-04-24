document.addEventListener('DOMContentLoaded', function () {
    // Configuration des couleurs
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
                    font: {
                        size: 10
                    }
                }
            },
            title: {
                display: false
            }
        }
    };


    new Chart(document.getElementById('osChart'), {
        type: 'pie',
        data: {
            labels: osData.labels,
            datasets: [{
                data: osData.data,
                backgroundColor: colorPalette,
                borderWidth: 1
            }]
        },
        options: commonOptions
    });


    new Chart(document.getElementById('browserChart'), {
        type: 'pie',
        data: {
            labels: browserData.labels,
            datasets: [{
                data: browserData.data,
                backgroundColor: colorPalette,
                borderWidth: 1
            }]
        },
        options: commonOptions
    });


    new Chart(document.getElementById('resolutionChart'), {
        type: 'bar',
        data: {
            labels: resolutionData.labels,
            datasets: [{
                label: 'Visites',
                data: resolutionData.data,
                backgroundColor: colorPalette[0],
                borderWidth: 1
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });


    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: typeData.labels,
            datasets: [{
                data: typeData.data,
                backgroundColor: colorPalette,
                borderWidth: 1
            }]
        },
        options: commonOptions
    });

    fetch('/api/analytics/visitorsByDate')
        .then(response => response.json())
        .then(data => {
            new Chart(document.getElementById('visitorsTimeChart'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Nombre de visites',
                        data: data.data,
                        borderColor: colorPalette[0],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 8
                                },
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            alert('Erreur lors du chargement des données chronologiques : ' + error.message);
        });
});