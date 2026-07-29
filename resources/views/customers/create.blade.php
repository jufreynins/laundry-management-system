@extends('layouts.app')

@section('title', 'Add Customer')
@section('header', 'Add Customer')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @if ($locations->count() > 1)
            <div class="mb-3">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select" required>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="location_id" value="{{ $locations->first()?->id }}">
            @endif
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address') }}">
            </div>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ old('state') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" value="{{ old('zip') }}">
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="marketing_consent" value="1" class="form-check-input" id="marketing_consent">
                <label class="form-check-label" for="marketing_consent">Customer consents to marketing communications</label>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Customer</button>
            <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
