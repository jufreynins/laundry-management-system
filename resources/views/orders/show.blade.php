@extends('layouts.app')

@section('title', $order->order_number)
@section('header', $order->order_number)

@section('content')
<div class="row g-3">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header">Order Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">{{ $order->customer->name }} ({{ $order->customer->customer_number }})</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8"><span class="badge bg-info status-badge">{{ $order->status->label() }}</span></dd>
                    <dt class="col-sm-4">Intake Channel</dt>
                    <dd class="col-sm-8">{{ $order->intake_channel->label() }}</dd>
                    <dt class="col-sm-4">Intake Date</dt>
                    <dd class="col-sm-8">{{ $order->intake_at->format('m/d/Y g:i A') }}</dd>
                    <dt class="col-sm-4">Promised Date</dt>
                    <dd class="col-sm-8">{{ $order->promised_at?->format('m/d/Y g:i A') ?? '-' }}</dd>
                    <dt class="col-sm-4">Rush</dt>
                    <dd class="col-sm-8">{{ $order->rush ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-4">Weight / Items / Bags</dt>
                    <dd class="col-sm-8">{{ $order->weight_lbs ?? '-' }} lbs / {{ $order->item_count ?? '-' }} items / {{ $order->bag_count ?? '-' }} bags</dd>
                    <dt class="col-sm-4">Assigned To</dt>
                    <dd class="col-sm-8">{{ $order->assignedUser?->name ?? 'Unassigned' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Service Items</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Service</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->unit_price, 2) }}</td>
                            <td>${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($order->stain_notes || $order->customer_instructions || $order->internal_notes)
        <div class="card mb-3">
            <div class="card-header">Notes</div>
            <div class="card-body">
                @if ($order->stain_notes)<p><strong>Stain/Damage:</strong> {{ $order->stain_notes }}</p>@endif
                @if ($order->customer_instructions)<p><strong>Customer Instructions:</strong> {{ $order->customer_instructions }}</p>@endif
                @if ($order->internal_notes)<p><strong>Internal Notes:</strong> {{ $order->internal_notes }}</p>@endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">Status History</div>
            <ul class="list-group list-group-flush">
                @foreach ($order->statusHistory as $history)
                <li class="list-group-item">
                    {{ $history->created_at->format('m/d/Y g:i A') }} &mdash;
                    {{ $history->from_status ? \App\Enums\OrderStatus::from($history->from_status)->label() . ' → ' : '' }}
                    {{ \App\Enums\OrderStatus::from($history->to_status)->label() }}
                    ({{ $history->changedBy?->name ?? 'System' }})
                    @if ($history->reason)<br><small class="text-muted">{{ $history->reason }}</small>@endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Payment Summary</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Subtotal</dt>
                    <dd class="col-sm-6 text-end">${{ number_format($order->subtotal, 2) }}</dd>
                    <dt class="col-sm-6">Discount</dt>
                    <dd class="col-sm-6 text-end">-${{ number_format($order->discount_amount, 2) }}</dd>
                    <dt class="col-sm-6">Tax</dt>
                    <dd class="col-sm-6 text-end">${{ number_format($order->tax_amount, 2) }}</dd>
                    <dt class="col-sm-6">Tip</dt>
                    <dd class="col-sm-6 text-end">${{ number_format($order->tip_amount, 2) }}</dd>
                    <dt class="col-sm-6 fw-bold">Total</dt>
                    <dd class="col-sm-6 text-end fw-bold">${{ number_format($order->total, 2) }}</dd>
                    <dt class="col-sm-6">Amount Paid</dt>
                    <dd class="col-sm-6 text-end">${{ number_format($order->amount_paid, 2) }}</dd>
                    <dt class="col-sm-6 text-danger">Balance Due</dt>
                    <dd class="col-sm-6 text-end text-danger">${{ number_format($order->balance_due, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
