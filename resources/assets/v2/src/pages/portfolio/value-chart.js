import PortfolioDashboard from "../../api/v1/portfolio/dashboard.js";
import {Chart} from 'chart.js';

let chart = null;

export default () => ({
    loading: false,

    init() {
        this.loadChart();
    },

    loadChart() {
        this.loading = true;
        const dashboard = new PortfolioDashboard();
        const end = new Date();
        const start = new Date();
        start.setMonth(start.getMonth() - 6);

        dashboard.valueOverTime(start, end).then((response) => {
            const data = response.data.data || [];
            this.drawChart(data);
            this.loading = false;
        }).catch(() => {
            this.loading = false;
        });
    },

    drawChart(data) {
        const canvas = document.getElementById('portfolio-value-chart');
        if (!canvas) return;

        if (chart) {
            chart.destroy();
        }

        const labels = data.map(d => d.date);
        const values = data.map(d => parseFloat(d.total_value));
        const costs = data.map(d => parseFloat(d.total_cost));

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Portfolio Value',
                        data: values,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.3,
                    },
                    {
                        label: 'Cost Basis',
                        data: costs,
                        borderColor: '#6c757d',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: true, position: 'top'},
                    tooltip: {mode: 'index', intersect: false},
                },
                scales: {
                    x: {display: true},
                    y: {display: true, beginAtZero: false},
                }
            }
        });
    }
});
