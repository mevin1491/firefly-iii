import {api} from "../../../boot/axios";
import {format} from "date-fns";

export default class PortfolioDashboard {
    summary() {
        return api.get('/api/v1/portfolio/dashboard/summary');
    }

    valueOverTime(start, end) {
        let params = {};
        if (start) params.start = format(start, 'y-MM-dd');
        if (end) params.end = format(end, 'y-MM-dd');
        return api.get('/api/v1/portfolio/dashboard/value-over-time', {params});
    }

    holdingsBreakdown() {
        return api.get('/api/v1/portfolio/dashboard/holdings-breakdown');
    }

    assetAllocation() {
        return api.get('/api/v1/portfolio/dashboard/asset-allocation');
    }

    profitLoss(start, end) {
        let params = {};
        if (start) params.start = format(start, 'y-MM-dd');
        if (end) params.end = format(end, 'y-MM-dd');
        return api.get('/api/v1/portfolio/dashboard/profit-loss', {params});
    }
}
