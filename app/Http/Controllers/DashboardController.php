<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $locationId = $user->scopedLocationId();

        return view('dashboard', [
            'user' => $user,
            'ordersToday' => $this->reportService->ordersToday($locationId),
            'revenueToday' => $this->reportService->revenueToday($locationId),
            'amountDue' => $this->reportService->totalAmountDue($locationId),
            'ordersByStatus' => $this->reportService->ordersByStatus($locationId),
            'readyForPickupCount' => $this->reportService->readyForPickupCount($locationId),
            'overdueOrders' => $this->reportService->overdueOrders($locationId, 10),
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
