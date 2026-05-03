/*
 * Portfolio Dashboard Charts
 * Renders charts on the portfolio tracker dashboard using Chart.js
 */
(function () {
    'use strict';

    // Chart.js defaults for portfolio
    Chart.defaults.global.responsive = true;
    Chart.defaults.global.maintainAspectRatio = false;
    Chart.defaults.global.animation.duration = 800;

    // Detect dark mode
    var isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var textColor = isDark ? '#bec5cb' : '#333';
    var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';

    /**
     * Platform Allocation - Doughnut Chart
     */
    function loadPlatformAllocation() {
        var canvas = document.getElementById('platform-allocation-chart');
        if (!canvas) return;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', platformAllocationUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4 || xhr.status !== 200) return;

            var data = JSON.parse(xhr.responseText);

            if (!data.labels || data.labels.length === 0) {
                canvas.parentNode.innerHTML = '<p class="text-center text-muted" style="padding:40px;">No data available</p>';
                return;
            }

            new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    legend: {
                        position: 'bottom',
                        labels: { fontColor: textColor }
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, chartData) {
                                var label = chartData.labels[tooltipItem.index] || '';
                                var value = chartData.datasets[0].data[tooltipItem.index];
                                var total = chartData.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = ((value / total) * 100).toFixed(1);
                                return label + ': ' + numberFormat(value) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            });
        };
        xhr.send();
    }

    /**
     * Asset Type Allocation - Pie Chart
     */
    function loadAssetTypeAllocation() {
        var canvas = document.getElementById('asset-type-chart');
        if (!canvas) return;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', assetTypeAllocationUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4 || xhr.status !== 200) return;

            var data = JSON.parse(xhr.responseText);

            if (!data.labels || data.labels.length === 0) {
                canvas.parentNode.innerHTML = '<p class="text-center text-muted" style="padding:40px;">No data available</p>';
                return;
            }

            new Chart(canvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    legend: {
                        position: 'bottom',
                        labels: { fontColor: textColor }
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, chartData) {
                                var label = chartData.labels[tooltipItem.index] || '';
                                var value = chartData.datasets[0].data[tooltipItem.index];
                                return label + ': ' + numberFormat(value);
                            }
                        }
                    }
                }
            });
        };
        xhr.send();
    }

    /**
     * Historical Portfolio Value - Line Chart
     */
    function loadHistoricalValue() {
        var canvas = document.getElementById('portfolio-history-chart');
        if (!canvas) return;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', historicalValueUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4 || xhr.status !== 200) return;

            var data = JSON.parse(xhr.responseText);

            if (!data.datasets || data.datasets.length === 0) {
                canvas.parentNode.innerHTML = '<p class="text-center text-muted" style="padding:40px;">No historical data yet. Data will appear after syncs over time.</p>';
                return;
            }

            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: { datasets: data.datasets },
                options: {
                    scales: {
                        xAxes: [{
                            type: 'time',
                            time: {
                                unit: 'week',
                                displayFormats: { week: 'MMM D' }
                            },
                            gridLines: { color: gridColor },
                            ticks: { fontColor: textColor }
                        }],
                        yAxes: [{
                            gridLines: { color: gridColor },
                            ticks: {
                                fontColor: textColor,
                                callback: function (value) {
                                    return numberFormat(value);
                                }
                            }
                        }]
                    },
                    legend: {
                        labels: { fontColor: textColor }
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, chartData) {
                                var label = chartData.datasets[tooltipItem.datasetIndex].label || '';
                                return label + ': ' + numberFormat(tooltipItem.yLabel);
                            }
                        }
                    }
                }
            });
        };
        xhr.send();
    }

    /**
     * Top Holdings - Horizontal Bar Chart
     */
    function loadTopHoldings() {
        var canvas = document.getElementById('top-holdings-chart');
        if (!canvas) return;

        var xhr = new XMLHttpRequest();
        xhr.open('GET', topHoldingsUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4 || xhr.status !== 200) return;

            var data = JSON.parse(xhr.responseText);

            if (!data.labels || data.labels.length === 0) {
                canvas.parentNode.innerHTML = '<p class="text-center text-muted" style="padding:40px;">No holdings data available</p>';
                return;
            }

            new Chart(canvas.getContext('2d'), {
                type: 'horizontalBar',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    scales: {
                        xAxes: [{
                            gridLines: { color: gridColor },
                            ticks: {
                                fontColor: textColor,
                                callback: function (value) {
                                    return numberFormat(value);
                                }
                            }
                        }],
                        yAxes: [{
                            gridLines: { display: false },
                            ticks: { fontColor: textColor }
                        }]
                    },
                    legend: {
                        labels: { fontColor: textColor }
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, chartData) {
                                var label = chartData.datasets[tooltipItem.datasetIndex].label || '';
                                return label + ': ' + numberFormat(tooltipItem.xLabel);
                            }
                        }
                    }
                }
            });
        };
        xhr.send();
    }

    /**
     * Format numbers with commas and 2 decimal places
     */
    function numberFormat(value) {
        if (typeof value !== 'number') {
            value = parseFloat(value);
        }
        if (isNaN(value)) return '0.00';
        return value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Load all charts when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        loadPlatformAllocation();
        loadAssetTypeAllocation();
        loadHistoricalValue();
        loadTopHoldings();
    });
})();
