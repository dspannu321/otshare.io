<?php

namespace App\Http\Controllers;

use App\Services\LandingStatsService;
use Illuminate\Contracts\View\View;

class LandingPageController extends Controller
{
    public function __invoke(LandingStatsService $landingStats): View
    {
        return view('landing', [
            'landingStats' => $landingStats->summary(),
        ]);
    }
}
