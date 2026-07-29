@extends('layouts.app')

@section('title', $user->name)
@section('header', $user->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Email</dt>
            <dd class="col-sm-8">{{ $user->email }}</dd>
            <dt class="col-sm-4">Role</dt>
            <dd class="col-sm-8">{{ $user->role->label() }}</dd>
            <dt class="col-sm-4">Location</dt>
            <dd class="col-sm-8">{{ $user->location?->name ?? 'All Locations' }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">{{ $user->active ? 'Active' : 'Inactive' }}</dd>
            <dt class="col-sm-4">Last Login</dt>
            <dd class="col-sm-8">{{ $user->last_login_at?->format('m/d/Y g:i A') ?? 'Never' }}</dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
</div>
@endsection
