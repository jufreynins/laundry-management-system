@extends('layouts.app')

@section('title', 'Add Supplier')
@section('header', 'Add Supplier')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Location (optional — shared if blank)</label>
                <select name="location_id" class="form-control">
                    <option value="">All Locations</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Name</label>
                <input type="text" name="contact_name" class="form-control">
            </div>
            <div class="form-row">
                <div class="form-group mb-0">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Supplier</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
