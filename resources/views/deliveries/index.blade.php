@extends('layouts.app')

@section('title', 'Deliveries')
@section('header', 'Deliveries')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
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
                    <td>
                        <span class="status {{ match ($delivery->status) {
                            \App\Enums\DeliveryStatus::SCHEDULED => 'status-yellow',
                            \App\Enums\DeliveryStatus::EN_ROUTE => 'status-blue',
                            \App\Enums\DeliveryStatus::COMPLETED => 'status-green',
                            \App\Enums\DeliveryStatus::FAILED, \App\Enums\DeliveryStatus::CANCELLED => 'status-red',
                        } }}">{{ $delivery->status->label() }}</span>
                    </td>
                    <td>${{ number_format($delivery->fee, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No deliveries scheduled</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $deliveries->links() }}
</div>
@endsection
