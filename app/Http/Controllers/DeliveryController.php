<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleDeliveryRequest;
use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Models\Delivery;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryException;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveryService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Delivery::class);

        $user = $request->user();
        $query = Delivery::query()->with(['order.customer', 'driver'])->latest('scheduled_at');

        if ($user->hasRole(\App\Enums\UserRole::DRIVER)) {
            $query->where('driver_id', $user->id);
        } elseif ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        $deliveries = $query->paginate(20);

        return view('deliveries.index', ['deliveries' => $deliveries]);
    }

    public function create(Order $order): View
    {
        $this->authorize('create', Delivery::class);
        $this->authorize('view', $order);

        return view('deliveries.create', [
            'order' => $order,
            'zones' => DeliveryZone::where('location_id', $order->location_id)->where('active', true)->get(),
            'drivers' => User::where('location_id', $order->location_id)->where('role', 'driver')->where('active', true)->get(),
        ]);
    }

    public function store(ScheduleDeliveryRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $delivery = $this->deliveryService->schedule($order, $request->validated(), $request->user());

        return redirect()->route('orders.show', $order)->with('status', 'Delivery scheduled: '.$delivery->type->label());
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery): RedirectResponse
    {
        try {
            $this->deliveryService->updateStatus(
                $delivery,
                \App\Enums\DeliveryStatus::from($request->validated('status')),
                $request->validated('proof_notes'),
                $request->user(),
            );
        } catch (DeliveryException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Delivery status updated.');
    }

    public function assignDriver(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorize('update', $delivery);

        $request->validate([
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('location_id', $delivery->location_id),
            ],
        ]);

        $this->deliveryService->assignDriver($delivery, $request->integer('driver_id') ?: null, $request->user());

        return back()->with('status', 'Driver assignment updated.');
    }
}
