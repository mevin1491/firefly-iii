<div class="card mb-2">
    <div class="card-header">
        <h3 class="card-title">Asset Allocation</h3>
    </div>
    <div class="card-body">
        <template x-if="loading">
            <p class="text-center"><em class="fa-solid fa-spinner fa-spin"></em> Loading...</p>
        </template>
        <div class="row">
            <div class="col-md-4">
                <h6 class="text-center">By Platform</h6>
                <canvas id="allocation-platform-chart" style="max-height: 250px;"></canvas>
            </div>
            <div class="col-md-4">
                <h6 class="text-center">By Asset Class</h6>
                <canvas id="allocation-class-chart" style="max-height: 250px;"></canvas>
            </div>
            <div class="col-md-4">
                <h6 class="text-center">By Currency</h6>
                <canvas id="allocation-currency-chart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>
