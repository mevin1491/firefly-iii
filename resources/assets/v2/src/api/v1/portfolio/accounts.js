import {api} from "../../../boot/axios";

export default class PortfolioAccounts {
    index() {
        return api.get('/api/v1/portfolio/accounts');
    }

    store(data) {
        return api.post('/api/v1/portfolio/accounts', data);
    }

    update(id, data) {
        return api.put('/api/v1/portfolio/accounts/' + id, data);
    }

    destroy(id) {
        return api.delete('/api/v1/portfolio/accounts/' + id);
    }

    sync() {
        return api.post('/api/v1/portfolio/sync');
    }

    syncAccount(id) {
        return api.post('/api/v1/portfolio/sync/' + id);
    }

    importCsv(accountId, file) {
        let formData = new FormData();
        formData.append('file', file);
        formData.append('import_type', 'holdings');
        return api.post('/api/v1/portfolio/import/' + accountId, formData, {
            headers: {'Content-Type': 'multipart/form-data'}
        });
    }
}
