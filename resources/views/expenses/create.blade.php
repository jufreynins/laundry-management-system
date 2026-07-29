@extends('layouts.app')

@section('title', 'Add Expense')
@section('header', 'Add Expense')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('expenses.store') }}">
            @csrf
            @if ($locations->count() > 1)
            <div class="mb-3">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select" required>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="location_id" value="{{ $locations->first()?->id }}">
            @endif
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="expense_category_id" class="form-select" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Supplier (optional)</label>
                <select name="supplier_id" class="form-select">
                    <option value="">None</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Expense Date</label>
                <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Expense</button>
            <a href="{{ route('expenses.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
