@extends('layouts.app')

@section('title', 'Inventory')
@section('header', 'Inventory')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('create', App\Models\InventoryItem::class)
    <a href="{{ route('inventory.create') }}" class="btn btn-primary">Add Item</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Name</th><th>Supplier</th><th>Quantity</th><th>Reorder Threshold</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($items as $item)
                <tr>
                    <td><a href="{{ route('inventory.show', $item) }}">{{ $item->name }}</a></td>
                    <td>{{ $item->supplier?->name ?? '-' }}</td>
                    <td>{{ $item->current_quantity }} {{ $item->unit }}</td>
                    <td>{{ $item->reorder_threshold }} {{ $item->unit }}</td>
                    <td>
                        @if ($item->isBelowReorderThreshold())
                            <span class="badge bg-danger status-badge">Reorder Needed</span>
                        @else
                            <span class="badge bg-success status-badge">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No inventory items yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $items->links() }}</div>
@endsection
