import PortfolioDashboard from "../../api/v1/portfolio/dashboard.js";

export default () => ({
    loading: false,
    unrealizedList: [],
    realizedList: [],

    formatNumber(val) {
        if (null === val || undefined === val) return '-';
        const num = parseFloat(val);
        return num >= 1 ? num.toLocaleString(undefined, {maximumFractionDigits: 4}) : num.toFixed(8);
    },

    formatCurrency(value, currency) {
        if (null === value || undefined === value) return '-';
        try {
            return new Intl.NumberFormat(undefined, {style: 'currency', currency: currency}).format(parseFloat(value));
        } catch (e) {
            return parseFloat(value).toFixed(2) + ' ' + currency;
        }
    },

    init() {
        this.loading = true;
        const dashboard = new PortfolioDashboard();
        dashboard.profitLoss().then((response) => {
            const data = response.data.data || {};
            this.unrealizedList = data.unrealized || [];
            this.realizedList = data.realized || [];
            this.loading = false;
        }).catch(() => {
            this.loading = false;
        });
    }
});
