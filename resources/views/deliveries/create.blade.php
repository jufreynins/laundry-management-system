@extends('layouts.app')

@section('title', 'Schedule Delivery')
@section('header', 'Schedule Pickup/Delivery for '.$order->order_number)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="{{ route('deliveries.store', $order) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-control" required>
                    @foreach (\App\Enums\DeliveryType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Scheduled Date/Time</label>
                <input type="datetime-local" name="scheduled_at" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Delivery Zone</label>
                <select name="delivery_zone_id" class="form-control">
                    <option value="">No zone / no fee</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }} (${{ number_format($zone->fee, 2) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="{{ $order->customer->address }}" required>
            </div>
            <div class="form-row cols-3">
                <div class="form-group mb-0">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ $order->customer->city }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ $order->customer->state }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Zip</label>
                    <input type="text" name="zip" class="form-control" value="{{ $order->customer->zip }}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-control">
                    <option value="">Unassigned</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Schedule</button>
                <a href="{{ route('orders.show', $order) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
