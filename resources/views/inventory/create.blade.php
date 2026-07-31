@extends('layouts.app')

@section('title', 'Add Inventory Item')
@section('header', 'Add Inventory Item')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            @if ($locations->count() > 1)
            <div class="form-group">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-control" required>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="location_id" value="{{ $locations->first()?->id }}">
            @endif
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Unit (e.g. bottle, box, gallon)</label>
                <input type="text" name="unit" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="">None</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <div class="form-group mb-0">
                    <label class="form-label">Starting Quantity</label>
                    <input type="number" step="0.01" min="0" name="current_quantity" class="form-control" value="0" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Reorder Threshold</label>
                    <input type="number" step="0.01" min="0" name="reorder_threshold" class="form-control" value="0" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Cost per Unit ($)</label>
                <input type="number" step="0.01" min="0" name="cost_per_unit" class="form-control">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Item</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
