@extends('layouts.app')

@section('title', $user->name)
@section('header', $user->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="detail-list">
            <dt>Email</dt>
            <dd>{{ $user->email }}</dd>
            <dt>Role</dt>
            <dd>{{ $user->role->label() }}</dd>
            <dt>Location</dt>
            <dd>{{ $user->location?->name ?? 'All Locations' }}</dd>
            <dt>Status</dt>
            <dd>{{ $user->active ? 'Active' : 'Inactive' }}</dd>
            <dt>Last Login</dt>
            <dd>{{ $user->last_login_at?->format('m/d/Y g:i A') ?? 'Never' }}</dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline">Edit</a>
    </div>
</div>
@endsection
