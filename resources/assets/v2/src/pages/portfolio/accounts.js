import PortfolioAccounts from "../../api/v1/portfolio/accounts.js";

export default () => ({
    loading: false,
    syncing: false,
    showAddForm: false,
    accountList: [],
    newAccount: {name: '', platform: ''},

    platformBadge(platform) {
        const map = {
            'moomoo': 'text-bg-primary',
            'fsmone': 'text-bg-success',
            'luno': 'text-bg-warning',
        };
        return map[platform] || 'text-bg-secondary';
    },

    init() {
        this.loadAccounts();
    },

    loadAccounts() {
        this.loading = true;
        const api = new PortfolioAccounts();
        api.index().then((response) => {
            this.accountList = response.data.data || [];
            this.loading = false;
        }).catch(() => {
            this.loading = false;
        });
    },

    addAccount() {
        if (!this.newAccount.name || !this.newAccount.platform) return;
        const api = new PortfolioAccounts();
        api.store(this.newAccount).then(() => {
            this.showAddForm = false;
            this.newAccount = {name: '', platform: ''};
            this.loadAccounts();
        });
    },

    deleteAccount(id) {
        if (!confirm('Delete this portfolio account?')) return;
        const api = new PortfolioAccounts();
        api.destroy(id).then(() => {
            this.loadAccounts();
        });
    },

    syncAll() {
        this.syncing = true;
        const api = new PortfolioAccounts();
        api.sync().then(() => {
            this.syncing = false;
            this.loadAccounts();
            window.location.reload();
        }).catch(() => {
            this.syncing = false;
        });
    },

    syncOne(id) {
        this.syncing = true;
        const api = new PortfolioAccounts();
        api.syncAccount(id).then(() => {
            this.syncing = false;
            this.loadAccounts();
        }).catch(() => {
            this.syncing = false;
        });
    },

    importCsv(accountId, event) {
        const file = event.target.files[0];
        if (!file) return;

        const api = new PortfolioAccounts();
        api.importCsv(accountId, file).then((response) => {
            const msg = response.data.message || 'Import completed.';
            alert(msg);
            this.loadAccounts();
        }).catch((err) => {
            alert('Import failed: ' + (err.response?.data?.message || err.message));
        });

        event.target.value = '';
    }
});
