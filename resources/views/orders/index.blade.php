@extends('layouts.app')

@section('title', 'Orders')
@section('header', 'Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-control" onchange="this.form.submit()">
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
        <table class="table mb-0">
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
                    <td class="cell-mono"><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td>{{ $order->customer->name }}</td>
                    <td>
                        <span class="status status-{{ $order->status->color() }}">{{ $order->status->label() }}</span>
                    </td>
                    <td>{{ $order->promised_at?->format('m/d/Y g:i A') ?? '-' }}</td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>${{ number_format($order->balance_due, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No orders found</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>
@endsection
