@extends('layouts.app')

@section('title', $location->name)
@section('header', $location->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="detail-list">
            <dt>Address</dt>
            <dd>{{ $location->address }}, {{ $location->city }}, {{ $location->state }} {{ $location->zip }}</dd>
            <dt>Phone</dt>
            <dd>{{ $location->phone }}</dd>
            <dt>Timezone</dt>
            <dd>{{ $location->timezone }}</dd>
            <dt>Status</dt>
            <dd>{{ $location->active ? 'Active' : 'Inactive' }}</dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.locations.edit', $location) }}" class="btn btn-sm btn-outline">Edit</a>
        <a href="{{ route('admin.settings.index', ['location_id' => $location->id]) }}" class="btn btn-sm btn-outline">Settings</a>
    </div>
</div>
@endsection
