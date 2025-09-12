const revenueCtx = document.getElementById('revenueChart').getContext('2d');

new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [
            {
                label: 'Total Donations ($)',
                data: monthlyData.map(item => item.total_donations),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#007bff',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            },
            {
                label: 'Number of Donations',
                data: monthlyData.map(item => item.donation_count),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: false,
                yAxisID: 'y1',
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.datasetIndex === 0) {
                            label += '$' + context.parsed.y.toLocaleString();
                        } else {
                            label += context.parsed.y + ' donations';
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Month'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Donation Amount ($)'
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Number of Donations'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Growth Chart - Focus on platform growth metrics
const growthCtx = document.getElementById('growthChart').getContext('2d');

new Chart(growthCtx, {
    type: 'bar',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [
            {
                label: 'New Campaigns',
                data: monthlyData.map(item => item.new_campaigns),
                backgroundColor: '#17a2b8',
                borderColor: '#138496',
                borderWidth: 1
            },
            {
                label: 'New Users',
                data: monthlyData.map(item => item.new_users),
                backgroundColor: '#ffc107',
                borderColor: '#e0a800',
                borderWidth: 1
            },
            {
                label: 'Completed Campaigns',
                data: monthlyData.map(item => item.completed_campaigns),
                backgroundColor: '#28a745',
                borderColor: '#1e7e34',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Month'
                }
            },
            y: {
                beginAtZero: true,
                display: true,
                title: {
                    display: true,
                    text: 'Count'
                },
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Utility function to open campaign view
function openCampaignView(campaignId) {
    window.open(`../../campaign/view?id=${campaignId}`, '_blank');
}

// Admin action functions
function handleFundReport(reportId, fundId, action) {
    if (!confirm(`Are you sure you want to ${action} this campaign?`)) return;
    
    fetch('../ajax/handle_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `report_id=${reportId}&fund_id=${fundId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function handleCommentReport(reportId, commentId, action) {
    if (!confirm(`Are you sure you want to ${action} this comment?`)) return;
    
    fetch('../ajax/handle_comment_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `report_id=${reportId}&comment_id=${commentId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function toggleFeature(fundId) {
    fetch('../ajax/toggle_feature.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `fund_id=${fundId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function toggleFreeze(fundId, action) {
    const actionText = action === 'freeze' ? 'freeze' : 'unfreeze';
    if (!confirm(`Are you sure you want to ${actionText} this campaign?`)) return;
    
    fetch('../ajax/toggle_freeze.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `fund_id=${fundId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}