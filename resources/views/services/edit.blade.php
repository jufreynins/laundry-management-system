@extends('layouts.app')

@section('title', 'Edit Service')
@section('header', 'Edit Service')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('services.update', $service) }}">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $service->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" {{ old('category', $service->category->value) === $category->value ? 'selected' : '' }}>{{ $category->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Pricing Type</label>
                <select name="pricing_type" class="form-select" required>
                    @foreach ($pricingTypes as $type)
                        <option value="{{ $type->value }}" {{ old('pricing_type', $service->pricing_type->value) === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Base Price ($)</label>
                    <input type="number" step="0.01" min="0" name="base_price" class="form-control" value="{{ old('base_price', $service->base_price) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Minimum Charge ($)</label>
                    <input type="number" step="0.01" min="0" name="minimum_charge" class="form-control" value="{{ old('minimum_charge', $service->minimum_charge) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Estimated Duration (minutes)</label>
                <input type="number" min="1" name="estimated_duration_minutes" class="form-control" value="{{ old('estimated_duration_minutes', $service->estimated_duration_minutes) }}">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="taxable" value="1" class="form-check-input" id="taxable" {{ $service->taxable ? 'checked' : '' }}>
                <label class="form-check-label" for="taxable">Taxable</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="rush_eligible" value="1" class="form-check-input" id="rush_eligible" {{ $service->rush_eligible ? 'checked' : '' }}>
                <label class="form-check-label" for="rush_eligible">Rush Eligible</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ $service->active ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Active</label>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('services.show', $service) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
