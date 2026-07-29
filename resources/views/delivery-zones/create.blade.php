@extends('layouts.app')

@section('title', 'Add Delivery Zone')
@section('header', 'Add Delivery Zone')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('delivery-zones.store') }}">
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
                <label class="form-label">Zone Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Delivery Fee ($)</label>
                <input type="number" step="0.01" min="0" name="fee" class="form-control" value="0.00" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Zone</button>
            <a href="{{ route('delivery-zones.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
