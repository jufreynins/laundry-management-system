@extends('layouts.app')

@section('title', $customer->name)
@section('header', $customer->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="detail-list">
            <dt>Customer Number</dt>
            <dd>{{ $customer->customer_number }}</dd>
            <dt>Phone</dt>
            <dd>{{ $customer->phone }}</dd>
            <dt>Email</dt>
            <dd>{{ $customer->email ?? '-' }}</dd>
            <dt>Address</dt>
            <dd>{{ $customer->address }} {{ $customer->city }} {{ $customer->state }} {{ $customer->zip }}</dd>
            <dt>Location</dt>
            <dd>{{ $customer->location->name }}</dd>
            <dt>Status</dt>
            <dd>{{ $customer->active ? 'Active' : 'Inactive' }}</dd>
            <dt>Marketing Consent</dt>
            <dd>{{ $customer->marketing_consent ? 'Yes' : 'No' }}</dd>
            <dt>Notes</dt>
            <dd>{{ $customer->notes ?? '-' }}</dd>
        </dl>
    </div>
    @can('update', $customer)
    <div class="card-footer">
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
    @endcan
</div>

<div class="card mt-3" style="max-width: 640px;">
    <div class="card-header"><span class="card-title">Order History</span></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Total</th><th>Balance Due</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                <tr>
                    <td class="cell-mono"><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td>{{ $order->intake_at->format('m/d/Y') }}</td>
                    <td><span class="status status-blue">{{ $order->status->label() }}</span></td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>${{ number_format($order->balance_due, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No orders yet</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>
@endsection
