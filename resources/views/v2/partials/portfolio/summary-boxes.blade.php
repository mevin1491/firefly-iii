<div class="row mb-2" x-data="summary">
    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h4 class="hover-expand">
                    <template x-if="loading">
                        <span><em class="fa-solid fa-spinner fa-spin"></em></span>
                    </template>
                    <template x-if="!loading">
                        <template x-for="(total, index) in totals" :key="index">
                            <span>
                                <span x-text="formatCurrency(total.total_value, total.currency_code)"></span>
                                <span x-show="index < totals.length - 1">, </span>
                            </span>
                        </template>
                    </template>
                </h4>
                <p class="d-none d-sm-block">Total Portfolio Value</p>
            </div>
            <span class="small-box-icon"><i class="fa-solid fa-wallet"></i></span>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h4 class="hover-expand">
                    <template x-if="loading">
                        <span><em class="fa-solid fa-spinner fa-spin"></em></span>
                    </template>
                    <template x-if="!loading">
                        <template x-for="(total, index) in totals" :key="index">
                            <span>
                                <span x-text="formatCurrency(total.total_cost, total.currency_code)"></span>
                                <span x-show="index < totals.length - 1">, </span>
                            </span>
                        </template>
                    </template>
                </h4>
                <p class="d-none d-sm-block">Total Cost Basis</p>
            </div>
            <span class="small-box-icon"><i class="fa-solid fa-coins"></i></span>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h4 class="hover-expand">
                    <template x-if="loading">
                        <span><em class="fa-solid fa-spinner fa-spin"></em></span>
                    </template>
                    <template x-if="!loading">
                        <template x-for="(total, index) in totals" :key="index">
                            <span>
                                <span x-text="formatCurrency(total.unrealized_pnl, total.currency_code)"></span>
                                <span x-show="index < totals.length - 1">, </span>
                            </span>
                        </template>
                    </template>
                </h4>
                <p class="d-none d-sm-block">Unrealized P&L</p>
            </div>
            <span class="small-box-icon"><i class="fa-solid fa-chart-line"></i></span>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-12 col-sm-12">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h4 x-text="accountCount"></h4>
                <p class="d-none d-sm-block">Connected Accounts</p>
            </div>
            <span class="small-box-icon"><i class="fa-solid fa-link"></i></span>
        </div>
    </div>
</div>
