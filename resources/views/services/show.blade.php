@extends('layouts.app')

@section('title', $service->name)
@section('header', $service->name)

@section('content')
<div class="card mb-3" style="max-width: 640px;">
    <div class="card-body">
        <dl class="detail-list">
            <dt>Category</dt>
            <dd>{{ $service->category->label() }}</dd>
            <dt>Pricing Type</dt>
            <dd>{{ $service->pricing_type->label() }}</dd>
            <dt>Base Price</dt>
            <dd>${{ number_format($service->base_price, 2) }}</dd>
            <dt>Minimum Charge</dt>
            <dd>{{ $service->minimum_charge ? '$'.number_format($service->minimum_charge, 2) : '-' }}</dd>
            <dt>Taxable</dt>
            <dd>{{ $service->taxable ? 'Yes' : 'No' }}</dd>
            <dt>Rush Eligible</dt>
            <dd>{{ $service->rush_eligible ? 'Yes' : 'No' }}</dd>
            <dt>Est. Duration</dt>
            <dd>{{ $service->estimated_duration_minutes ? $service->estimated_duration_minutes.' minutes' : '-' }}</dd>
            <dt>Status</dt>
            <dd>{{ $service->active ? 'Active' : 'Inactive' }}</dd>
        </dl>
    </div>
    @can('update', $service)
    <div class="card-footer">
        <a href="{{ route('services.edit', $service) }}" class="btn btn-sm btn-outline">Edit</a>
    </div>
    @endcan
</div>

@if ($service->servicePrices->isNotEmpty())
<div class="card" style="max-width: 640px;">
    <div class="card-header"><span class="card-title">Location-Specific Pricing</span></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Location</th><th>Price</th><th>Active</th></tr></thead>
            <tbody>
                @foreach ($service->servicePrices as $price)
                <tr>
                    <td>{{ $price->location->name }}</td>
                    <td>${{ number_format($price->price, 2) }}</td>
                    <td>{{ $price->active ? 'Yes' : 'No' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
