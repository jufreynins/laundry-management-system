@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="row col-4 mb-3">
    <div class="card card-accent-primary">
        <div class="stat">
            <div class="stat-icon stat-icon-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1.5"/><circle cx="20" cy="21" r="1.5"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Orders Today</div>
                <div class="stat-value-row"><span class="stat-value">{{ $ordersToday }}</span></div>
            </div>
        </div>
    </div>
    <div class="card card-accent-green">
        <div class="stat">
            <div class="stat-icon stat-icon-green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="2" x2="12" y2="22"/><path d="M16 6H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 010 7H7"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Revenue Today</div>
                <div class="stat-value-row"><span class="stat-value">${{ number_format($revenueToday, 2) }}</span></div>
            </div>
        </div>
    </div>
    <div class="card card-accent-yellow">
        <div class="stat">
            <div class="stat-icon stat-icon-yellow">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 21V3h14v18l-3-2-3 2-3-2-3 2-2-2z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Amount Due (Open Orders)</div>
                <div class="stat-value-row"><span class="stat-value">${{ number_format($amountDue, 2) }}</span></div>
            </div>
        </div>
    </div>
    <div class="card card-accent-azure">
        <div class="stat">
            <div class="stat-icon stat-icon-azure">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-label">Ready for Pickup</div>
                <div class="stat-value-row"><span class="stat-value">{{ $readyForPickupCount }}</span></div>
            </div>
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
                <span class="badge badge-{{ $status->color() }}">{{ $ordersByStatus[$status->value] ?? 0 }}</span>
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
