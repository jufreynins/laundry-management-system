@extends('layouts.app')

@section('title', $order->order_number)
@section('header', $order->order_number)

@section('content')
@php
$statusColor = match ($order->status->value) {
    'ready_for_pickup', 'completed' => 'green',
    'cancelled' => 'red',
    'draft', 'on_hold' => 'yellow',
    default => 'blue',
};
@endphp
<div class="row col-8-4">
    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Order Details</span></div>
            <div class="card-body">
                <dl class="detail-list">
                    <dt>Customer</dt>
                    <dd>{{ $order->customer->name }} ({{ $order->customer->customer_number }})</dd>
                    <dt>Status</dt>
                    <dd><span class="status status-{{ $statusColor }}">{{ $order->status->label() }}</span></dd>
                    <dt>Intake Channel</dt>
                    <dd>{{ $order->intake_channel->label() }}</dd>
                    <dt>Intake Date</dt>
                    <dd>{{ $order->intake_at->format('m/d/Y g:i A') }}</dd>
                    <dt>Promised Date</dt>
                    <dd>{{ $order->promised_at?->format('m/d/Y g:i A') ?? '-' }}</dd>
                    <dt>Rush</dt>
                    <dd>{{ $order->rush ? 'Yes' : 'No' }}</dd>
                    <dt>Weight / Items / Bags</dt>
                    <dd>{{ $order->weight_lbs ?? '-' }} lbs / {{ $order->item_count ?? '-' }} items / {{ $order->bag_count ?? '-' }} bags</dd>
                    <dt>Assigned To</dt>
                    <dd>{{ $order->assignedUser?->name ?? 'Unassigned' }}</dd>
                    <dt>Customer Tracking Link</dt>
                    <dd><a href="{{ route('public.tracking.show', $order->tracking_token) }}" target="_blank">{{ route('public.tracking.show', $order->tracking_token) }}</a></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Service Items</span></div>
            <div class="table-responsive">
                <table class="table mb-0">
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
            <div class="card-header"><span class="card-title">Notes</span></div>
            <div class="card-body">
                @if ($order->stain_notes)<p><strong>Stain/Damage:</strong> {{ $order->stain_notes }}</p>@endif
                @if ($order->customer_instructions)<p><strong>Customer Instructions:</strong> {{ $order->customer_instructions }}</p>@endif
                @if ($order->internal_notes)<p><strong>Internal Notes:</strong> {{ $order->internal_notes }}</p>@endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><span class="card-title">Status History</span></div>
            <ul class="list-group">
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

    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Payment Summary</span></div>
            <div class="card-body">
                <dl class="detail-list">
                    <dt>Subtotal</dt>
                    <dd class="text-end">${{ number_format($order->subtotal, 2) }}</dd>
                    <dt>Discount</dt>
                    <dd class="text-end">-${{ number_format($order->discount_amount, 2) }}</dd>
                    <dt>Tax</dt>
                    <dd class="text-end">${{ number_format($order->tax_amount, 2) }}</dd>
                    <dt>Tip</dt>
                    <dd class="text-end">${{ number_format($order->tip_amount, 2) }}</dd>
                    <dt class="fw-bold">Total</dt>
                    <dd class="text-end fw-bold">${{ number_format($order->total, 2) }}</dd>
                    <dt>Amount Paid</dt>
                    <dd class="text-end">${{ number_format($order->amount_paid, 2) }}</dd>
                    <dt class="text-danger">Balance Due</dt>
                    <dd class="text-end text-danger">${{ number_format($order->balance_due, 2) }}</dd>
                </dl>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="btn btn-sm btn-outline">Print Receipt</a>
                <a href="{{ route('orders.claim-ticket', $order) }}" target="_blank" class="btn btn-sm btn-outline">Claim Ticket</a>
            </div>
        </div>

        @can('create', App\Models\Payment::class)
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Payments</span></div>
            <ul class="list-group">
                @forelse ($order->payments as $payment)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>{{ $payment->payment_reference }} &mdash; ${{ number_format($payment->amount, 2) }} ({{ $payment->method->label() }})</span>
                        @php
                        $paymentStatusColor = match ($payment->status->value) {
                            'completed' => 'green',
                            'pending' => 'yellow',
                            'failed', 'voided' => 'red',
                            default => 'blue',
                        };
                        @endphp
                        <span class="status status-{{ $paymentStatusColor }}">{{ $payment->status->label() }}</span>
                    </div>
                    @can('void', $payment)
                    @if ($payment->status->value === 'completed')
                    <form method="POST" action="{{ route('payments.void', $payment) }}" class="mt-2 d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Void reason" required>
                        <button type="submit" class="btn btn-sm btn-outline text-nowrap">Void</button>
                    </form>
                    @endif
                    @endcan
                    @can('refund', $payment)
                    @if (in_array($payment->status->value, ['completed', 'partially_refunded']))
                    <form method="POST" action="{{ route('payments.refund', $payment) }}" class="mt-2 d-flex gap-2">
                        @csrf
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required>
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Refund reason" required>
                        <button type="submit" class="btn btn-sm btn-outline text-nowrap">Refund</button>
                    </form>
                    @endif
                    @endcan
                </li>
                @empty
                <li class="list-group-item text-muted small">No payments recorded yet.</li>
                @endforelse
            </ul>
            @if ($order->balance_due > 0)
            <div class="card-body">
                <form method="POST" action="{{ route('payments.store', $order) }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <div class="form-row">
                        <div class="form-group mb-0">
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" value="{{ number_format($order->balance_due, 2) }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <select name="method" class="form-control" required>
                                <option value="{{ \App\Enums\PaymentMethod::CASH->value }}">{{ \App\Enums\PaymentMethod::CASH->label() }}</option>
                                <option value="{{ \App\Enums\PaymentMethod::EXTERNAL->value }}">{{ \App\Enums\PaymentMethod::EXTERNAL->label() }}</option>
                            </select>
                        </div>
                    </div>
                    <input type="text" name="reference_note" class="form-control mt-2" placeholder="Reference note (check #, terminal, etc.)">
                    <button type="submit" class="btn btn-sm btn-primary mt-2">Record Manual Payment</button>
                </form>
                <form method="POST" action="{{ route('online-payments.store', $order) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline w-100">Pay Online (Hosted Checkout)</button>
                </form>
            </div>
            @endif
        </div>
        @endcan

        @can('update', $order)
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Update Status</span></div>
            <div class="card-body">
                @if (count($allowedNext) > 0)
                <form method="POST" action="{{ route('orders.status', $order) }}" class="mb-3">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <select name="status" class="form-control" required>
                            @foreach ($allowedNext as $next)
                                <option value="{{ $next }}">{{ \App\Enums\OrderStatus::from($next)->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                </form>
                @else
                <p class="text-muted small mb-3">No standard transitions available from this status.</p>
                @endif

                @if (auth()->user()->role === \App\Enums\UserRole::OWNER)
                <details>
                    <summary class="small text-muted">Owner override</summary>
                    <form method="POST" action="{{ route('orders.status', $order) }}" class="mt-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="override" value="1">
                        <div class="form-group">
                            <select name="status" class="form-control" required>
                                @foreach (\App\Enums\OrderStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea name="reason" class="form-control" placeholder="Reason for override (required)" required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-check">
                                <input type="checkbox" name="confirm_override" value="1" required>
                                I confirm this manual override
                            </label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning">Override Status</button>
                    </form>
                </details>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Assign Staff</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('orders.assign', $order) }}">
                    @csrf
                    @method('PATCH')
                    <select name="assigned_user_id" class="form-control mb-2">
                        <option value="">Unassigned</option>
                        @foreach ($staffOptions as $staff)
                            <option value="{{ $staff->id }}" {{ $order->assigned_user_id === $staff->id ? 'selected' : '' }}>{{ $staff->name }} ({{ $staff->role->label() }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline">Update Assignment</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Intake Photos</span></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse ($order->photos as $photo)
                        <a href="{{ route('orders.photos.show', [$order, $photo]) }}" target="_blank" class="small">Photo #{{ $photo->id }}</a>
                    @empty
                        <span class="text-muted small">No photos uploaded.</span>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('orders.photos.store', $order) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="photo" accept="image/*" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-sm btn-outline">Upload Photo</button>
                </form>
            </div>
        </div>
        @endcan

        @can('create', App\Models\Delivery::class)
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="card-title">Pickup / Delivery</span>
                <a href="{{ route('deliveries.create', $order) }}" class="btn btn-sm btn-outline">Schedule</a>
            </div>
            <ul class="list-group">
                @forelse ($order->deliveries as $delivery)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>{{ $delivery->type->label() }} &mdash; {{ $delivery->scheduled_at->format('m/d/Y g:i A') }}</span>
                        @php
                        $deliveryStatusColor = match ($delivery->status->value) {
                            'completed' => 'green',
                            'scheduled' => 'yellow',
                            'en_route' => 'blue',
                            default => 'red',
                        };
                        @endphp
                        <span class="status status-{{ $deliveryStatusColor }}">{{ $delivery->status->label() }}</span>
                    </div>
                    <small class="text-muted">Driver: {{ $delivery->driver?->name ?? 'Unassigned' }}</small>
                </li>
                @empty
                <li class="list-group-item text-muted small">No pickup or delivery scheduled.</li>
                @endforelse
            </ul>
        </div>
        @endcan
    </div>
</div>
@endsection
