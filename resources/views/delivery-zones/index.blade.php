@extends('layouts.app')

@section('title', 'Delivery Zones')
@section('header', 'Delivery Zones')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('create', App\Models\DeliveryZone::class)
    <a href="{{ route('delivery-zones.create') }}" class="btn btn-primary">Add Zone</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Name</th><th>Location</th><th>Fee</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($zones as $zone)
                <tr>
                    <td>{{ $zone->name }}</td>
                    <td>{{ $zone->location->name }}</td>
                    <td>${{ number_format($zone->fee, 2) }}</td>
                    <td>{{ $zone->active ? 'Active' : 'Inactive' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No delivery zones configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
