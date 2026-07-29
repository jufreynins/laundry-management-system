@extends('layouts.app')

@section('title', 'New Order')
@section('header', 'New Order')

@section('content')
<div class="card" style="max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="{{ route('orders.store') }}">
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
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select a customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->customer_number }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Intake Channel</label>
                <select name="intake_channel" class="form-select" required>
                    @foreach (\App\Enums\IntakeChannel::cases() as $channel)
                        <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Promised Date/Time</label>
                    <input type="datetime-local" name="promised_at" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Weight (lbs)</label>
                    <input type="number" step="0.01" min="0" name="weight_lbs" class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Item Count</label>
                    <input type="number" min="0" name="item_count" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bag Count</label>
                    <input type="number" min="0" name="bag_count" class="form-control">
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" name="rush" value="1" class="form-check-input" id="rush">
                <label class="form-check-label" for="rush">Rush Order</label>
            </div>

            <hr>
            <h6>Service Items</h6>
            <div id="order-items-container">
                <div class="order-item-row row align-items-end mb-2">
                    <div class="col-6">
                        <label class="form-label">Service</label>
                        <select name="items[0][service_id]" class="form-select" required>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->pricing_type->label() }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control" required>
                    </div>
                </div>
            </div>
            <button type="button" id="add-order-item" class="btn btn-sm btn-outline-secondary mb-3">Add Another Item</button>

            <template id="order-item-template">
                <div class="order-item-row row align-items-end mb-2">
                    <div class="col-6">
                        <label class="form-label">Service</label>
                        <select name="items[][service_id]" class="form-select" required>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->pricing_type->label() }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" min="0.01" name="items[][quantity]" class="form-control" required>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-order-item">Remove</button>
                    </div>
                </div>
            </template>

            <hr>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Discount Amount ($)</label>
                    <input type="number" step="0.01" min="0" name="discount_amount" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tip Amount ($)</label>
                    <input type="number" step="0.01" min="0" name="tip_amount" class="form-control" value="0">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Stain / Damage Notes</label>
                <textarea name="stain_notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Garment Condition Flags</label>
                @foreach (['tear' => 'Tear', 'missing_button' => 'Missing Button', 'broken_zipper' => 'Broken Zipper', 'color_bleed_risk' => 'Color Bleed Risk', 'delicate_fabric' => 'Delicate Fabric', 'items_in_pockets' => 'Items Left in Pockets'] as $flag => $label)
                <div class="form-check form-check-inline">
                    <input type="checkbox" name="garment_flags[{{ $flag }}]" value="1" class="form-check-input" id="flag_{{ $flag }}">
                    <label class="form-check-label" for="flag_{{ $flag }}">{{ $label }}</label>
                </div>
                @endforeach
            </div>

            <div class="mb-3">
                <label class="form-label">Customer-Declared Item Count</label>
                <input type="number" min="0" name="customer_declared_item_count" class="form-control">
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" name="customer_acknowledged" value="1" class="form-check-input" id="customer_acknowledged">
                <label class="form-check-label" for="customer_acknowledged">Customer has acknowledged the intake condition notes</label>
            </div>

            <div class="mb-3">
                <label class="form-label">Customer Instructions</label>
                <textarea name="customer_instructions" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Internal Notes</label>
                <textarea name="internal_notes" class="form-control" rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Create Order</button>
            <a href="{{ route('orders.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
