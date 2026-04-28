import PortfolioDashboard from "../../api/v1/portfolio/dashboard.js";

export default () => ({
    loading: false,
    totals: [],
    accountCount: 0,

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
        dashboard.summary().then((response) => {
            this.totals = response.data.data.totals || [];
            this.accountCount = response.data.data.account_count || 0;
            this.loading = false;
        }).catch(() => {
            this.loading = false;
        });
    }
});
