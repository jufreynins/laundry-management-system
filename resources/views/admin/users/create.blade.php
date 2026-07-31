@extends('layouts.app')

@section('title', 'Add User')
@section('header', 'Add User')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="12" maxlength="64">
                <div class="form-text">Minimum 12 characters.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="12" maxlength="64">
            </div>
            @php $isOwner = auth()->user()->role === \App\Enums\UserRole::OWNER; @endphp
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        @if ($isOwner || !in_array($role, [\App\Enums\UserRole::OWNER, \App\Enums\UserRole::MANAGER]))
                        <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                        @endif
                    @endforeach
                </select>
                @if (!$isOwner)
                <div class="form-text">Only an Owner can create Owner or Manager accounts.</div>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-control">
                    @if ($isOwner)
                    <option value="">All Locations (Owner only)</option>
                    @endif
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" {{ (old('location_id', $isOwner ? null : $locations->first()?->id)) == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
