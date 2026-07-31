@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="row col-4 mb-3">
    <div class="card">
        <div class="card-body">
            <h6 class="text-muted small mb-1">Orders Today</h6>
            <p class="mb-0 fs-4 fw-bold">{{ $ordersToday }}</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h6 class="text-muted small mb-1">Revenue Today</h6>
            <p class="mb-0 fs-4 fw-bold">${{ number_format($revenueToday, 2) }}</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h6 class="text-muted small mb-1">Amount Due (Open Orders)</h6>
            <p class="mb-0 fs-4 fw-bold">${{ number_format($amountDue, 2) }}</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h6 class="text-muted small mb-1">Ready for Pickup</h6>
            <p class="mb-0 fs-4 fw-bold">{{ $readyForPickupCount }}</p>
        </div>
    </div>
</div>

<div class="row col-2">
    <div class="card">
        <div class="card-header"><span class="card-title">Orders by Status</span></div>
        <div class="list-group">
            @foreach ($statuses as $status)
            <div class="list-group-item">
                {{ $status->label() }}
                <span class="badge">{{ $ordersByStatus[$status->value] ?? 0 }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Overdue Orders</span></div>
        <div class="list-group">
            @forelse ($overdueOrders as $order)
            <a href="{{ route('orders.show', $order) }}" class="list-group-item">
                <div>
                    {{ $order->order_number }} &mdash; {{ $order->customer->name }}
                    <span class="text-danger small d-block">Promised {{ $order->promised_at->format('m/d/Y g:i A') }}</span>
                </div>
            </a>
            @empty
            <div class="list-group-item text-muted small">No overdue orders.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
