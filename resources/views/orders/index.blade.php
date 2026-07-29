@extends('layouts.app')

@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            @foreach (\App\Enums\OrderStatus::cases() as $s)
                <option value="{{ $s->value }}" {{ $status === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
            @endforeach
        </select>
    </form>
    @can('create', App\Models\Order::class)
    <a href="{{ route('orders.create') }}" class="btn btn-primary">New Order</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Promised</th>
                    <th>Total</th>
                    <th>Balance Due</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                <tr>
                    <td><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td>{{ $order->customer->name }}</td>
                    <td><span class="badge bg-info status-badge">{{ $order->status->label() }}</span></td>
                    <td>{{ $order->promised_at?->format('m/d/Y g:i A') ?? '-' }}</td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>${{ number_format($order->balance_due, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
@endsection
