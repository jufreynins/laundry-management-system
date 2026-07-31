@extends('layouts.app')

@section('title', 'Edit Customer')
@section('header', 'Edit Customer')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
            </div>
            <div class="form-row cols-3">
                <div class="form-group mb-0">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ old('state', $customer->state) }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" value="{{ old('zip', $customer->zip) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="marketing_consent" value="1" {{ $customer->marketing_consent ? 'checked' : '' }}>
                    Customer consents to marketing communications
                </label>
            </div>
            <div class="form-group">
                <label class="form-label d-block">Order Update Notifications</label>
                <div class="d-flex gap-3">
                    <label class="form-check">
                        <input type="checkbox" name="notify_email" value="1" {{ $customer->notify_email ? 'checked' : '' }}>
                        Email
                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="notify_sms" value="1" {{ $customer->notify_sms ? 'checked' : '' }}>
                        SMS
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="active" value="1" {{ $customer->active ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $customer->notes) }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
