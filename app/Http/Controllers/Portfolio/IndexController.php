<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Portfolio;

use FireflyIII\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        app('view')->share('title', 'Portfolio Tracker');
        app('view')->share('mainTitleIcon', 'fa-chart-line');
    }

    public function index(): View
    {
        $subTitle = 'Portfolio Overview';

        return view('v2.portfolio.index', compact('subTitle'));
    }
}
