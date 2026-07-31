@extends('layouts.app')

@section('title', 'Add Delivery Zone')
@section('header', 'Add Delivery Zone')

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('delivery-zones.store') }}">
            @csrf
            @if ($locations->count() > 1)
            <div class="form-group">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-control" required>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="location_id" value="{{ $locations->first()?->id }}">
            @endif
            <div class="form-group">
                <label class="form-label">Zone Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Delivery Fee ($)</label>
                <input type="number" step="0.01" min="0" name="fee" class="form-control" value="0.00" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Zone</button>
                <a href="{{ route('delivery-zones.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
