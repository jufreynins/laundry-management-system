@extends('layouts.app')

@section('title', $service->name)
@section('header', $service->name)

@section('content')
<div class="card mb-3" style="max-width: 640px;">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Category</dt>
            <dd class="col-sm-8">{{ $service->category->label() }}</dd>
            <dt class="col-sm-4">Pricing Type</dt>
            <dd class="col-sm-8">{{ $service->pricing_type->label() }}</dd>
            <dt class="col-sm-4">Base Price</dt>
            <dd class="col-sm-8">${{ number_format($service->base_price, 2) }}</dd>
            <dt class="col-sm-4">Minimum Charge</dt>
            <dd class="col-sm-8">{{ $service->minimum_charge ? '$'.number_format($service->minimum_charge, 2) : '-' }}</dd>
            <dt class="col-sm-4">Taxable</dt>
            <dd class="col-sm-8">{{ $service->taxable ? 'Yes' : 'No' }}</dd>
            <dt class="col-sm-4">Rush Eligible</dt>
            <dd class="col-sm-8">{{ $service->rush_eligible ? 'Yes' : 'No' }}</dd>
            <dt class="col-sm-4">Est. Duration</dt>
            <dd class="col-sm-8">{{ $service->estimated_duration_minutes ? $service->estimated_duration_minutes.' minutes' : '-' }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">{{ $service->active ? 'Active' : 'Inactive' }}</dd>
        </dl>
    </div>
    @can('update', $service)
    <div class="card-footer">
        <a href="{{ route('services.edit', $service) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
    @endcan
</div>

@if ($service->servicePrices->isNotEmpty())
<div class="card" style="max-width: 640px;">
    <div class="card-header">Location-Specific Pricing</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
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
