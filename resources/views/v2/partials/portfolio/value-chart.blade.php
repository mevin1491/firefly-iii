<div class="col-12">
    <div class="card mb-2">
        <div class="card-header">
            <h3 class="card-title">Portfolio Value Over Time</h3>
        </div>
        <div class="card-body">
            <template x-if="loading">
                <p class="text-center"><em class="fa-solid fa-spinner fa-spin"></em> Loading chart...</p>
            </template>
            <canvas id="portfolio-value-chart" style="height: 350px;"></canvas>
        </div>
    </div>
</div>
