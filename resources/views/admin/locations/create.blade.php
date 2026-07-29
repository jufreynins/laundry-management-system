@extends('layouts.app')

@section('title', 'Add Location')
@section('header', 'Add Location')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.locations.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
            </div>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ old('state') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" value="{{ old('zip') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Timezone</label>
                <select name="timezone" class="form-select" required>
                    @foreach (['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','America/Phoenix','America/Anchorage','Pacific/Honolulu'] as $tz)
                        <option value="{{ $tz }}" {{ old('timezone', 'America/New_York') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Location</button>
            <a href="{{ route('admin.locations.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
