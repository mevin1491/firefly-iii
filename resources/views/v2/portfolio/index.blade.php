@extends('layout.v2')
@section('content')

    <div class="app-content">
        <div class="container-fluid">
            @include('v2.partials.portfolio.summary-boxes')

            <div class="row mb-2" x-data="valueChart">
                @include('v2.partials.portfolio.value-chart')
            </div>

            <div class="row mb-2" x-data="holdings">
                @include('v2.partials.portfolio.holdings-table')
            </div>

            <div class="row mb-2">
                <div class="col-xl-6 col-lg-12 col-sm-12" x-data="allocation">
                    @include('v2.partials.portfolio.allocation-charts')
                </div>
                <div class="col-xl-6 col-lg-12 col-sm-12" x-data="profitLoss">
                    @include('v2.partials.portfolio.profit-loss')
                </div>
            </div>

            <div class="row mb-2" x-data="accounts">
                @include('v2.partials.portfolio.accounts-panel')
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    @vite(['src/pages/portfolio/portfolio.js'])
@endsection
