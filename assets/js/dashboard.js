/**
 * Comby Analytics Dashboard JS
 * Initializes Charts and interactive elements.
 * Supports multiple chart types and real-time data updates.
 */

(function($) {
    'use strict';

    const charts = {};

    function initCharts(data) {
        // 1. Trends Chart (Line)
        const trendsCtx = document.getElementById('trendsChart');
        if (trendsCtx) {
            if (charts.trends) charts.trends.destroy();
            charts.trends = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: data.trends.labels,
                    datasets: [{
                        label: 'Page Views',
                        data: data.trends.data,
                        fill: true,
                        backgroundColor: 'rgba(56, 189, 248, 0.1)',
                        borderColor: '#38bdf8',
                        borderWidth: 3,
                        tension: 0.4,
                        pointBackgroundColor: '#818cf8',
                        pointBorderColor: '#fff',
                        pointRadius: 4
                    }]
                },
                options: getChartOptions()
            });
        }

        // 2. Browser Distribution (Pie)
        const browserCtx = document.getElementById('browserChart');
        if (browserCtx && data.browsers) {
            if (charts.browsers) charts.browsers.destroy();
            charts.browsers = new Chart(browserCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(data.browsers),
                    datasets: [{
                        data: Object.values(data.browsers),
                        backgroundColor: ['#38bdf8', '#818cf8', '#f43f5e', '#fbbf24'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
                }
            });
        }

        // 3. Device Distribution (Doughnut)
        const deviceCtx = document.getElementById('deviceChart');
        if (deviceCtx && data.devices) {
            if (charts.devices) charts.devices.destroy();
            charts.devices = new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data.devices),
                    datasets: [{
                        data: Object.values(data.devices),
                        backgroundColor: ['#38bdf8', '#818cf8', '#f43f5e'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
                }
            });
        }
    }

    function getChartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        };
    }

    $(document).ready(function() {
        // If data is already present (Mock environment)
        if (typeof comby_dashboard_data !== 'undefined') {
            initCharts(comby_dashboard_data);
        }

        // Handle Tab Switching in Mock Environment if needed
        $('.comby-tabs a').on('click', function(e) {
            // For WordPress, this is a standard link. 
            // For our Mock SPA, we handle it in administrative HTML.
        });
    });

    // Handle updates (Global access for mock environment)
    window.updateCombyCharts = initCharts;

})(typeof jQuery !== 'undefined' ? jQuery : null);
