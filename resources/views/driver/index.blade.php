@extends('layouts.app')

@section('title', 'My Deliveries')
@section('header', 'My Deliveries')

@section('content')
<div class="row g-3">
    @forelse ($deliveries as $delivery)
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-1">{{ $delivery->order->order_number }} &mdash; {{ $delivery->type->label() }}</h6>
                <p class="mb-1 small text-muted">{{ $delivery->order->customer->name }}</p>
                <p class="mb-1">{{ $delivery->address }}, {{ $delivery->city }}, {{ $delivery->state }} {{ $delivery->zip }}</p>
                <p class="mb-2 small">Scheduled: {{ $delivery->scheduled_at->format('m/d/Y g:i A') }}</p>
                <span class="badge bg-info status-badge mb-2">{{ $delivery->status->label() }}</span>

                <form method="POST" action="{{ route('deliveries.status', $delivery) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="form-select form-select-sm mb-2">
                        <option value="en_route" {{ $delivery->status->value === 'en_route' ? 'selected' : '' }}>En Route</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                    <textarea name="proof_notes" class="form-control form-control-sm mb-2" placeholder="Proof-of-delivery notes (required to complete)" rows="2"></textarea>
                    <button type="submit" class="btn btn-sm btn-primary w-100">Update</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <p class="text-muted">No deliveries currently assigned to you.</p>
    </div>
    @endforelse
</div>
@endsection
