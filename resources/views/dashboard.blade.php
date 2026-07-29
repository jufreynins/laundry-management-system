@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted small">Orders Today</h6>
                <p class="mb-0 fs-4">{{ $ordersToday }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted small">Revenue Today</h6>
                <p class="mb-0 fs-4">${{ number_format($revenueToday, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted small">Amount Due (Open Orders)</h6>
                <p class="mb-0 fs-4">${{ number_format($amountDue, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted small">Ready for Pickup</h6>
                <p class="mb-0 fs-4">{{ $readyForPickupCount }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Orders by Status</div>
            <ul class="list-group list-group-flush">
                @foreach ($statuses as $status)
                <li class="list-group-item d-flex justify-content-between">
                    {{ $status->label() }}
                    <span class="badge bg-secondary">{{ $ordersByStatus[$status->value] ?? 0 }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Overdue Orders</div>
            <ul class="list-group list-group-flush">
                @forelse ($overdueOrders as $order)
                <li class="list-group-item">
                    <a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a>
                    &mdash; {{ $order->customer->name }}
                    <span class="text-danger small d-block">Promised {{ $order->promised_at->format('m/d/Y g:i A') }}</span>
                </li>
                @empty
                <li class="list-group-item text-muted small">No overdue orders.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
