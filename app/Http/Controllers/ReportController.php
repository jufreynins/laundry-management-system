<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(UserRole::OWNER, UserRole::MANAGER, UserRole::ACCOUNTANT), 403);

        $user = $request->user();
        $locationId = $user->scopedLocationId();

        $from = $request->date('from') ? Carbon::parse($request->date('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->date('to') ? Carbon::parse($request->date('to'))->endOfDay() : now()->endOfDay();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'salesByService' => $this->reportService->salesByService($locationId, $from, $to),
            'salesByLocation' => $locationId === null ? $this->reportService->salesByLocation($from, $to) : [],
            'taxSummary' => $this->reportService->taxSummary($locationId, $from, $to),
            'paymentSummary' => $this->reportService->paymentSummaryByMethod($locationId, $from, $to),
        ]);
    }
}
