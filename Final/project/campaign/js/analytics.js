// Donation Timeline Chart
const donationCtx = document.getElementById('donationChart').getContext('2d');
const donationChart = new Chart(donationCtx, {
    type: 'line',
    data: {
        labels: dailyDataDate,
        datasets: [{
            label: 'Daily Donations',
            data: dailyDataAmount,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Progress Chart
const progressCtx = document.getElementById('progressChart').getContext('2d');
const progressChart = new Chart(progressCtx, {
    type: 'doughnut',
    data: {
        labels: ['Raised', 'Remaining'],
        datasets: [{
            data: progressChartData,
            backgroundColor: ['#27ae60', '#ecf0f1'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        cutout: '70%'
    }
});