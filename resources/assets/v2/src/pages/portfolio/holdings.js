import PortfolioDashboard from "../../api/v1/portfolio/dashboard.js";

export default () => ({
    loading: false,
    holdingsList: [],
    sortField: 'current_value',
    sortDirection: 'desc',

    get sortedHoldings() {
        return [...this.holdingsList].sort((a, b) => {
            let valA = a[this.sortField];
            let valB = b[this.sortField];

            if (!isNaN(parseFloat(valA)) && !isNaN(parseFloat(valB))) {
                valA = parseFloat(valA);
                valB = parseFloat(valB);
            }

            if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
    },

    sortBy(field) {
        if (this.sortField === field) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            this.sortDirection = 'desc';
        }
    },

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

    platformBadgeClass(platform) {
        const map = {
            'moomoo': 'text-bg-primary',
            'fsmone': 'text-bg-success',
            'luno': 'text-bg-warning',
        };
        return map[platform] || 'text-bg-secondary';
    },

    init() {
        this.loading = true;
        const dashboard = new PortfolioDashboard();
        dashboard.holdingsBreakdown().then((response) => {
            this.holdingsList = response.data.data || [];
            this.loading = false;
        }).catch(() => {
            this.loading = false;
        });
    }
});
