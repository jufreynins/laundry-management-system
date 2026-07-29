@extends('layouts.app')

@section('title', 'Deliveries')
@section('header', 'Deliveries')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Type</th>
                    <th>Scheduled</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Fee</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deliveries as $delivery)
                <tr>
                    <td><a href="{{ route('orders.show', $delivery->order) }}">{{ $delivery->order->order_number }}</a></td>
                    <td>{{ $delivery->type->label() }}</td>
                    <td>{{ $delivery->scheduled_at->format('m/d/Y g:i A') }}</td>
                    <td>{{ $delivery->driver?->name ?? 'Unassigned' }}</td>
                    <td><span class="badge bg-info status-badge">{{ $delivery->status->label() }}</span></td>
                    <td>${{ number_format($delivery->fee, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No deliveries scheduled.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $deliveries->links() }}</div>
@endsection
