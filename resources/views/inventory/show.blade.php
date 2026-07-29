@extends('layouts.app')

@section('title', $item->name)
@section('header', $item->name)

@section('content')
<div class="row g-3">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Current Quantity</dt>
                    <dd class="col-sm-7">{{ $item->current_quantity }} {{ $item->unit }}</dd>
                    <dt class="col-sm-5">Reorder Threshold</dt>
                    <dd class="col-sm-7">{{ $item->reorder_threshold }} {{ $item->unit }}</dd>
                    <dt class="col-sm-5">Supplier</dt>
                    <dd class="col-sm-7">{{ $item->supplier?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Cost per Unit</dt>
                    <dd class="col-sm-7">{{ $item->cost_per_unit ? '$'.number_format($item->cost_per_unit, 2) : '-' }}</dd>
                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7">
                        @if ($item->isBelowReorderThreshold())
                            <span class="badge bg-danger status-badge">Reorder Needed</span>
                        @else
                            <span class="badge bg-success status-badge">OK</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @can('recordTransaction', $item)
        <div class="card">
            <div class="card-header">Record Transaction</div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.transactions.store', $item) }}">
                    @csrf
                    <div class="mb-2">
                        <select name="type" class="form-select" required>
                            @foreach (\App\Enums\InventoryTransactionType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="number" step="0.01" name="quantity" class="form-control" placeholder="Quantity" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="reason" class="form-control" placeholder="Reason (optional)">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Record</button>
                </form>
            </div>
        </div>
        @endcan
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Transaction History</div>
            <ul class="list-group list-group-flush">
                @forelse ($item->transactions as $transaction)
                <li class="list-group-item">
                    {{ $transaction->created_at->format('m/d/Y g:i A') }} &mdash;
                    {{ $transaction->type->label() }}: {{ $transaction->quantity }} {{ $item->unit }}
                    (new total: {{ $transaction->quantity_after }})
                    <br><small class="text-muted">{{ $transaction->recordedBy->name }}@if($transaction->reason) &mdash; {{ $transaction->reason }}@endif</small>
                </li>
                @empty
                <li class="list-group-item text-muted small">No transactions recorded.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
