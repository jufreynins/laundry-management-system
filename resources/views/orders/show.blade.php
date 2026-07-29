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
        <div class="card mb-3">
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
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print Receipt</a>
                <a href="{{ route('orders.claim-ticket', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Claim Ticket</a>
            </div>
        </div>

        @can('create', App\Models\Payment::class)
        <div class="card mb-3">
            <div class="card-header">Payments</div>
            <ul class="list-group list-group-flush">
                @forelse ($order->payments as $payment)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>{{ $payment->payment_reference }} &mdash; ${{ number_format($payment->amount, 2) }} ({{ $payment->method->label() }})</span>
                        <span class="badge bg-secondary status-badge">{{ $payment->status->label() }}</span>
                    </div>
                    @can('void', $payment)
                    @if ($payment->status->value === 'completed')
                    <form method="POST" action="{{ route('payments.void', $payment) }}" class="mt-2 d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Void reason" required>
                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Void</button>
                    </form>
                    @endif
                    @endcan
                    @can('refund', $payment)
                    @if (in_array($payment->status->value, ['completed', 'partially_refunded']))
                    <form method="POST" action="{{ route('payments.refund', $payment) }}" class="mt-2 d-flex gap-2">
                        @csrf
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required>
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Refund reason" required>
                        <button type="submit" class="btn btn-sm btn-outline-warning text-nowrap">Refund</button>
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
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" value="{{ number_format($order->balance_due, 2) }}" required>
                        </div>
                        <div class="col-6">
                            <select name="method" class="form-select" required>
                                @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <input type="text" name="reference_note" class="form-control mt-2" placeholder="Reference note (check #, terminal, etc.)">
                    <button type="submit" class="btn btn-sm btn-primary mt-2">Record Payment</button>
                </form>
            </div>
            @endif
        </div>
        @endcan

        @can('update', $order)
        <div class="card mb-3">
            <div class="card-header">Update Status</div>
            <div class="card-body">
                @if (count($allowedNext) > 0)
                <form method="POST" action="{{ route('orders.status', $order) }}" class="mb-3">
                    @csrf
                    @method('PATCH')
                    <div class="mb-2">
                        <select name="status" class="form-select" required>
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
                        <div class="mb-2">
                            <select name="status" class="form-select" required>
                                @foreach (\App\Enums\OrderStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <textarea name="reason" class="form-control" placeholder="Reason for override (required)" required></textarea>
                        </div>
                        <div class="mb-2 form-check">
                            <input type="checkbox" name="confirm_override" value="1" class="form-check-input" id="confirm_override" required>
                            <label class="form-check-label" for="confirm_override">I confirm this manual override</label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning">Override Status</button>
                    </form>
                </details>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Assign Staff</div>
            <div class="card-body">
                <form method="POST" action="{{ route('orders.assign', $order) }}">
                    @csrf
                    @method('PATCH')
                    <select name="assigned_user_id" class="form-select mb-2">
                        <option value="">Unassigned</option>
                        @foreach ($staffOptions as $staff)
                            <option value="{{ $staff->id }}" {{ $order->assigned_user_id === $staff->id ? 'selected' : '' }}>{{ $staff->name }} ({{ $staff->role->label() }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Update Assignment</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Intake Photos</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse ($order->photos as $photo)
                        <a href="{{ route('orders.photos.show', [$order, $photo]) }}" target="_blank" class="text-decoration-none small">Photo #{{ $photo->id }}</a>
                    @empty
                        <span class="text-muted small">No photos uploaded.</span>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('orders.photos.store', $order) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="photo" accept="image/*" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Upload Photo</button>
                </form>
            </div>
        </div>
        @endcan

        @can('create', App\Models\Delivery::class)
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                Pickup / Delivery
                <a href="{{ route('deliveries.create', $order) }}" class="btn btn-sm btn-outline-primary">Schedule</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($order->deliveries as $delivery)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>{{ $delivery->type->label() }} &mdash; {{ $delivery->scheduled_at->format('m/d/Y g:i A') }}</span>
                        <span class="badge bg-info status-badge">{{ $delivery->status->label() }}</span>
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
