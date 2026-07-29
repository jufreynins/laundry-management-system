@extends('layouts.app')

@section('title', $customer->name)
@section('header', $customer->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Customer Number</dt>
            <dd class="col-sm-8">{{ $customer->customer_number }}</dd>
            <dt class="col-sm-4">Phone</dt>
            <dd class="col-sm-8">{{ $customer->phone }}</dd>
            <dt class="col-sm-4">Email</dt>
            <dd class="col-sm-8">{{ $customer->email ?? '-' }}</dd>
            <dt class="col-sm-4">Address</dt>
            <dd class="col-sm-8">{{ $customer->address }} {{ $customer->city }} {{ $customer->state }} {{ $customer->zip }}</dd>
            <dt class="col-sm-4">Location</dt>
            <dd class="col-sm-8">{{ $customer->location->name }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">{{ $customer->active ? 'Active' : 'Inactive' }}</dd>
            <dt class="col-sm-4">Marketing Consent</dt>
            <dd class="col-sm-8">{{ $customer->marketing_consent ? 'Yes' : 'No' }}</dd>
            <dt class="col-sm-4">Notes</dt>
            <dd class="col-sm-8">{{ $customer->notes ?? '-' }}</dd>
        </dl>
    </div>
    @can('update', $customer)
    <div class="card-footer">
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
    @endcan
</div>

<div class="card mt-3" style="max-width: 640px;">
    <div class="card-header">Order History</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Total</th><th>Balance Due</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                <tr>
                    <td><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td>{{ $order->intake_at->format('m/d/Y') }}</td>
                    <td><span class="badge bg-info status-badge">{{ $order->status->label() }}</span></td>
                    <td>${{ number_format($order->total, 2) }}</td>
                    <td>${{ number_format($order->balance_due, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3" style="max-width: 640px;">{{ $orders->links() }}</div>
@endsection
