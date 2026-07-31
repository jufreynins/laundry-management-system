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
        <table class="table mb-0">
            <thead><tr><th>Name</th><th>Location</th><th>Fee</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($zones as $zone)
                <tr>
                    <td class="cell-strong">{{ $zone->name }}</td>
                    <td>{{ $zone->location->name }}</td>
                    <td>${{ number_format($zone->fee, 2) }}</td>
                    <td>
                        @if ($zone->active)
                            <span class="status status-green">Active</span>
                        @else
                            <span class="status status-blue">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><div class="empty-state-title">No delivery zones configured</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
