<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Services\OrderPricingException;
use App\Services\OrderService;
use App\Services\OrderStatusException;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderStatusService $orderStatusService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $user = $request->user();
        $query = Order::query()->with(['customer', 'location'])->latest('intake_at');

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('orders.index', ['orders' => $orders, 'status' => $status]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Order::class);

        $user = $request->user();
        $scopedLocationId = $user->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        if ($redirect = $this->requireLocationExists($locations, 'orders.index')) {
            return $redirect;
        }

        $locationId = $scopedLocationId ?? $locations->first()?->id;

        $customers = Customer::where('active', true)
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderBy('name')
            ->get();

        if ($redirect = $this->requireNotEmpty($customers, 'customers.create', 'You need to add a Customer before you can create an order for them.')) {
            return $redirect;
        }

        $services = Service::where('active', true)->orderBy('category')->orderBy('name')->get();

        if ($redirect = $this->requireNotEmpty($services, 'services.index', 'You need at least one active Service before you can create an order. Ask an Owner or Manager to add one.')) {
            return $redirect;
        }

        return view('orders.create', compact('locations', 'customers', 'services'));
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $request->user();
        if ($scopedLocationId = $user->scopedLocationId()) {
            $data['location_id'] = $scopedLocationId;
        }

        try {
            $order = $this->orderService->createOrder($data, $user->id);
        } catch (OrderPricingException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Order created successfully.');
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('orders.show', [
            'order' => $order->load(['items.service', 'customer', 'location', 'statusHistory.changedBy', 'assignedUser', 'photos', 'payments', 'deliveries.driver']),
            'allowedNext' => \App\Services\OrderStatusTransitions::allowedNext($order->status),
            'staffOptions' => User::where('location_id', $order->location_id)->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orderStatusService->transition(
                $order,
                OrderStatus::from($request->validated('status')),
                $request->user(),
                $request->validated('reason'),
                $request->boolean('override'),
            );
        } catch (OrderStatusException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Order status updated.');
    }

    public function assign(AssignOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderStatusService->assignStaff($order, $request->validated('assigned_user_id'), $request->user());

        return back()->with('status', 'Order assignment updated.');
    }
}
