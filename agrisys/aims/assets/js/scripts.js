// Initialize Chart.js
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('cropChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Rice', 'Corn', 'Wheat'],
            datasets: [{
                label: 'Crop Distribution',
                data: [12, 19, 3],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});