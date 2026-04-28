import '../../boot/bootstrap.js';
import summary from './summary.js';
import valueChart from './value-chart.js';
import holdings from './holdings.js';
import allocation from './allocation.js';
import profitLoss from './profit-loss.js';
import accounts from './accounts.js';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Colors,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    TimeScale,
    Tooltip
} from "chart.js";
import 'chartjs-adapter-date-fns';

Chart.register({
    LineController,
    LineElement,
    ArcElement,
    BarController,
    TimeScale,
    PieController,
    DoughnutController,
    BarElement,
    Filler,
    Colors,
    LinearScale,
    CategoryScale,
    PointElement,
    Tooltip,
    Legend
});

const comps = {
    summary,
    valueChart,
    holdings,
    allocation,
    profitLoss,
    accounts
};

function loadPage(comps) {
    Object.keys(comps).forEach(comp => {
        let data = comps[comp]();
        Alpine.data(comp, () => data);
    });
    Alpine.start();
}

document.addEventListener('firefly-iii-bootstrapped', () => {
    console.log('Portfolio page loaded through event listener.');
    loadPage(comps);
});

if (window.bootstrapped) {
    console.log('Portfolio page loaded through window variable.');
    loadPage(comps);
}
