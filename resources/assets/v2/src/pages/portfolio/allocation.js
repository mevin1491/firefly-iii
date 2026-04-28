import PortfolioDashboard from "../../api/v1/portfolio/dashboard.js";
import {Chart} from 'chart.js';

let platformChart = null;
let classChart = null;
let currencyChart = null;

const COLORS = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6f42c1', '#fd7e14', '#20c997'];

export default () => ({
    loading: false,

    init() {
        this.loadData();
    },

    loadData() {
        this.loading = true;
        const dashboard = new PortfolioDashboard();
        dashboard.assetAllocation().then((response) => {
            const data = response.data.data || {};
            this.drawPieChart('allocation-platform-chart', data.by_platform || {}, platformChart, (c) => platformChart = c);
            this.drawPieChart('allocation-class-chart', data.by_class || {}, classChart, (c) => classChart = c);
            this.drawPieChart('allocation-currency-chart', data.by_currency || {}, currencyChart, (c) => currencyChart = c);
            this.loading = false;
        }).catch(() => {
            this.loading = false;
        });
    },

    drawPieChart(canvasId, data, existingChart, setter) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        if (existingChart) {
            existingChart.destroy();
        }

        const labels = Object.keys(data);
        const values = Object.values(data).map(v => parseFloat(v));

        const chart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: COLORS.slice(0, labels.length),
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {display: true, position: 'bottom'},
                }
            }
        });

        setter(chart);
    }
});
