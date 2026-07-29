@extends('layouts.app')

@section('title', 'Add User')
@section('header', 'Add User')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="12" maxlength="64">
                <div class="form-text">Minimum 12 characters.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="12" maxlength="64">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select">
                    <option value="">All Locations (Owner only)</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
