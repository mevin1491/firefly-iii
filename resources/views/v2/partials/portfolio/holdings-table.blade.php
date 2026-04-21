<div class="col-12">
    <div class="card mb-2">
        <div class="card-header">
            <h3 class="card-title">Holdings Breakdown</h3>
        </div>
        <div class="card-body p-0">
            <template x-if="loading">
                <p class="text-center p-3"><em class="fa-solid fa-spinner fa-spin"></em> Loading...</p>
            </template>
            <template x-if="!loading && holdingsList.length > 0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th @click="sortBy('platform')" style="cursor:pointer">Platform</th>
                                <th @click="sortBy('symbol')" style="cursor:pointer">Symbol</th>
                                <th @click="sortBy('name')" style="cursor:pointer">Name</th>
                                <th @click="sortBy('asset_class')" style="cursor:pointer">Class</th>
                                <th class="text-end" @click="sortBy('quantity')" style="cursor:pointer">Qty</th>
                                <th class="text-end" @click="sortBy('average_cost')" style="cursor:pointer">Avg Cost</th>
                                <th class="text-end" @click="sortBy('current_price')" style="cursor:pointer">Price</th>
                                <th class="text-end" @click="sortBy('current_value')" style="cursor:pointer">Value</th>
                                <th class="text-end" @click="sortBy('unrealized_pnl')" style="cursor:pointer">P&L</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(h, index) in sortedHoldings" :key="index">
                                <tr>
                                    <td><span class="badge" :class="platformBadgeClass(h.platform)" x-text="h.platform"></span></td>
                                    <td x-text="h.symbol"></td>
                                    <td x-text="h.name"></td>
                                    <td><span class="badge text-bg-secondary" x-text="h.asset_class"></span></td>
                                    <td class="text-end" x-text="formatNumber(h.quantity)"></td>
                                    <td class="text-end" x-text="formatCurrency(h.average_cost, h.currency_code)"></td>
                                    <td class="text-end" x-text="formatCurrency(h.current_price, h.currency_code)"></td>
                                    <td class="text-end" x-text="formatCurrency(h.current_value, h.currency_code)"></td>
                                    <td class="text-end" :class="parseFloat(h.unrealized_pnl) >= 0 ? 'text-success' : 'text-danger'" x-text="formatCurrency(h.unrealized_pnl, h.currency_code)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
            <template x-if="!loading && holdingsList.length === 0">
                <p class="text-center p-3 text-muted">No holdings found. Add a portfolio account and sync to get started.</p>
            </template>
        </div>
    </div>
</div>
