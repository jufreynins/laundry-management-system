@extends('layouts.app')

@section('title', $item->name)
@section('header', $item->name)

@section('content')
<div class="row col-2">
    <div>
        <div class="card mb-3">
            <div class="card-body">
                <dl class="detail-list">
                    <dt>Current Quantity</dt>
                    <dd>{{ $item->current_quantity }} {{ $item->unit }}</dd>
                    <dt>Reorder Threshold</dt>
                    <dd>{{ $item->reorder_threshold }} {{ $item->unit }}</dd>
                    <dt>Supplier</dt>
                    <dd>{{ $item->supplier?->name ?? '-' }}</dd>
                    <dt>Cost per Unit</dt>
                    <dd>{{ $item->cost_per_unit ? '$'.number_format($item->cost_per_unit, 2) : '-' }}</dd>
                    <dt>Status</dt>
                    <dd>
                        @if ($item->isBelowReorderThreshold())
                            <span class="status status-red">Reorder Needed</span>
                        @else
                            <span class="status status-green">OK</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @can('recordTransaction', $item)
        <div class="card">
            <div class="card-header"><span class="card-title">Record Transaction</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.transactions.store', $item) }}">
                    @csrf
                    <div class="form-group">
                        <select name="type" class="form-control" required>
                            @foreach (\App\Enums\InventoryTransactionType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" step="0.01" name="quantity" class="form-control" placeholder="Quantity" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="reason" class="form-control" placeholder="Reason (optional)">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Record</button>
                </form>
            </div>
        </div>
        @endcan
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Transaction History</span></div>
        <div class="list-group">
            @forelse ($item->transactions as $transaction)
            <div class="list-group-item">
                <div>
                    {{ $transaction->created_at->format('m/d/Y g:i A') }} &mdash;
                    {{ $transaction->type->label() }}: {{ $transaction->quantity }} {{ $item->unit }}
                    (new total: {{ $transaction->quantity_after }})
                    <span class="text-muted small d-block">{{ $transaction->recordedBy->name }}@if($transaction->reason) &mdash; {{ $transaction->reason }}@endif</span>
                </div>
            </div>
            @empty
            <div class="list-group-item text-muted small">No transactions recorded.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
