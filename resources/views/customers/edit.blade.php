@extends('layouts.app')

@section('title', 'Edit Customer')
@section('header', 'Edit Customer')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
            </div>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ old('state', $customer->state) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" value="{{ old('zip', $customer->zip) }}">
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="marketing_consent" value="1" class="form-check-input" id="marketing_consent" {{ $customer->marketing_consent ? 'checked' : '' }}>
                <label class="form-check-label" for="marketing_consent">Customer consents to marketing communications</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ $customer->active ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Active</label>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $customer->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
