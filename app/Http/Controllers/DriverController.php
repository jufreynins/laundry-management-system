<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $deliveries = Delivery::query()
            ->with(['order.customer'])
            ->where('driver_id', $request->user()->id)
            ->whereNotIn('status', ['completed', 'failed', 'cancelled'])
            ->orderBy('scheduled_at')
            ->get();

        return view('driver.index', ['deliveries' => $deliveries]);
    }
}
