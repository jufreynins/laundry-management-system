@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Welcome</h6>
                <p class="mb-0">{{ $user->name }} ({{ $user->role->label() }})</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Location</h6>
                <p class="mb-0">{{ $user->location?->name ?? 'All Locations' }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Last Login</h6>
                <p class="mb-0">{{ $user->last_login_at?->format('m/d/Y g:i A') ?? 'First login' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
