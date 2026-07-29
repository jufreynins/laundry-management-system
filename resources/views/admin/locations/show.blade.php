@extends('layouts.app')

@section('title', $location->name)
@section('header', $location->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Address</dt>
            <dd class="col-sm-8">{{ $location->address }}, {{ $location->city }}, {{ $location->state }} {{ $location->zip }}</dd>
            <dt class="col-sm-4">Phone</dt>
            <dd class="col-sm-8">{{ $location->phone }}</dd>
            <dt class="col-sm-4">Timezone</dt>
            <dd class="col-sm-8">{{ $location->timezone }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">{{ $location->active ? 'Active' : 'Inactive' }}</dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.locations.edit', $location) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        <a href="{{ route('admin.settings.index', ['location_id' => $location->id]) }}" class="btn btn-sm btn-outline-secondary">Settings</a>
    </div>
</div>
@endsection
