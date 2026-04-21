<div class="card mb-2">
    <div class="card-header">
        <h3 class="card-title">Profit & Loss</h3>
    </div>
    <div class="card-body p-0">
        <template x-if="loading">
            <p class="text-center p-3"><em class="fa-solid fa-spinner fa-spin"></em> Loading...</p>
        </template>
        <template x-if="!loading">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Avg Cost</th>
                            <th class="text-end">Current</th>
                            <th class="text-end">P&L</th>
                            <th class="text-end">Return %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in unrealizedList" :key="index">
                            <tr>
                                <td x-text="item.symbol"></td>
                                <td x-text="item.name"></td>
                                <td class="text-end" x-text="formatNumber(item.quantity)"></td>
                                <td class="text-end" x-text="formatCurrency(item.average_cost, item.currency_code)"></td>
                                <td class="text-end" x-text="formatCurrency(item.current_price, item.currency_code)"></td>
                                <td class="text-end" :class="parseFloat(item.unrealized_pnl) >= 0 ? 'text-success' : 'text-danger'" x-text="formatCurrency(item.unrealized_pnl, item.currency_code)"></td>
                                <td class="text-end" :class="parseFloat(item.return_pct) >= 0 ? 'text-success' : 'text-danger'">
                                    <span x-text="parseFloat(item.return_pct).toFixed(2) + '%'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>
