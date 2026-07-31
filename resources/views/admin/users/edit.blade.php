@extends('layouts.app')

@section('title', 'Edit User')
@section('header', 'Edit User')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            @php $isOwner = auth()->user()->role === \App\Enums\UserRole::OWNER; @endphp
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        @if ($isOwner || $role === $user->role || !in_array($role, [\App\Enums\UserRole::OWNER, \App\Enums\UserRole::MANAGER]))
                        <option value="{{ $role->value }}" {{ old('role', $user->role->value) === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                        @endif
                    @endforeach
                </select>
                @if (!$isOwner)
                <div class="form-text">Only an Owner can assign Owner or Manager roles.</div>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-control">
                    @if ($isOwner)
                    <option value="">All Locations (Owner only)</option>
                    @endif
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id', $user->location_id) == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="active" value="1" {{ $user->active ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
