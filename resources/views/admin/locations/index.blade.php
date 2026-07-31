@extends('layouts.app')

@section('title', 'Locations')
@section('header', 'Locations')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.locations.create') }}" class="btn btn-primary">Add Location</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Timezone</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($locations as $location)
                <tr>
                    <td class="cell-strong"><a href="{{ route('admin.locations.show', $location) }}">{{ $location->name }}</a></td>
                    <td>{{ $location->address }}, {{ $location->city }}, {{ $location->state }} {{ $location->zip }}</td>
                    <td>{{ $location->phone }}</td>
                    <td>{{ $location->timezone }}</td>
                    <td>
                        @if ($location->active)
                            <span class="status status-green">Active</span>
                        @else
                            <span class="status status-red">Inactive</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $locations->links() }}
</div>
@endsection
