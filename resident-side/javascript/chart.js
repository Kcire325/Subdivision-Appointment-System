

const ctx = document.getElementById('myChart');

if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Rejected', 'Pending'],
            datasets: [{
                label: 'Reservations',
                data: [
                    chartData.approved,
                    chartData.rejected,
                    chartData.pending
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',  
                    'rgba(220, 53, 69, 0.8)',   
                    'rgba(255, 193, 7, 0.8)'    
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed + ' reservation(s)';
                            return label;
                        }
                    }
                }
            }
        }
    });
}