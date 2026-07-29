@extends('layouts.app')

@section('title', 'Services')
@section('header', 'Services')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('create', App\Models\Service::class)
    <a href="{{ route('services.create') }}" class="btn btn-primary">Add Service</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Pricing</th>
                    <th>Base Price</th>
                    <th>Taxable</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                <tr>
                    <td><a href="{{ route('services.show', $service) }}">{{ $service->name }}</a></td>
                    <td>{{ $service->category->label() }}</td>
                    <td>{{ $service->pricing_type->label() }}</td>
                    <td>${{ number_format($service->base_price, 2) }}</td>
                    <td>{{ $service->taxable ? 'Yes' : 'No' }}</td>
                    <td>
                        @if ($service->active)
                            <span class="badge bg-success status-badge">Active</span>
                        @else
                            <span class="badge bg-secondary status-badge">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No services found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $services->links() }}</div>
@endsection
