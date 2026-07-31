@extends('layouts.app')

@section('title', 'Edit Location')
@section('header', 'Edit Location')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.locations.update', $location) }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $location->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $location->address) }}" required>
            </div>
            <div class="form-row cols-3">
                <div class="form-group mb-0">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $location->city) }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ old('state', $location->state) }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" value="{{ old('zip', $location->zip) }}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $location->phone) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Timezone</label>
                <select name="timezone" class="form-control" required>
                    @foreach (['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','America/Phoenix','America/Anchorage','Pacific/Honolulu'] as $tz)
                        <option value="{{ $tz }}" {{ old('timezone', $location->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="active" value="1" {{ $location->active ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.locations.show', $location) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
