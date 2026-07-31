@extends('layouts.app')

@section('title', 'Business Settings')
@section('header', 'Business Settings')

@section('content')
<form method="GET" class="mb-3" style="max-width: 300px;">
    <select name="location_id" class="form-control" onchange="this.form.submit()">
        @foreach ($locations as $loc)
            <option value="{{ $loc->id }}" {{ $loc->id === $location->id ? 'selected' : '' }}>{{ $loc->name }}</option>
        @endforeach
    </select>
</form>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update', $location) }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="sales_tax_enabled" value="1" {{ $settings['sales_tax_enabled'] === '1' ? 'checked' : '' }}>
                    Sales Tax Enabled
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Tax Rate (%)</label>
                <input type="number" step="0.001" min="0" max="100" name="tax_rate" class="form-control" value="{{ old('tax_rate', $settings['tax_rate']) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Minimum Order Amount ($)</label>
                <input type="number" step="0.01" min="0" name="minimum_order_amount" class="form-control" value="{{ old('minimum_order_amount', $settings['minimum_order_amount']) }}">
            </div>
            <div class="form-row">
                <div class="form-group mb-0">
                    <label class="form-label">Business Hours Open</label>
                    <input type="time" name="business_hours_open" class="form-control" value="{{ old('business_hours_open', $settings['business_hours_open']) }}">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Business Hours Close</label>
                    <input type="time" name="business_hours_close" class="form-control" value="{{ old('business_hours_close', $settings['business_hours_close']) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Terms and Liability Notice</label>
                <textarea name="terms_notice" class="form-control" rows="4">{{ old('terms_notice', $settings['terms_notice']) }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
