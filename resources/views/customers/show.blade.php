@extends('layouts.app')

@section('title', $customer->name)
@section('header', $customer->name)

@section('content')
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Customer Number</dt>
            <dd class="col-sm-8">{{ $customer->customer_number }}</dd>
            <dt class="col-sm-4">Phone</dt>
            <dd class="col-sm-8">{{ $customer->phone }}</dd>
            <dt class="col-sm-4">Email</dt>
            <dd class="col-sm-8">{{ $customer->email ?? '-' }}</dd>
            <dt class="col-sm-4">Address</dt>
            <dd class="col-sm-8">{{ $customer->address }} {{ $customer->city }} {{ $customer->state }} {{ $customer->zip }}</dd>
            <dt class="col-sm-4">Location</dt>
            <dd class="col-sm-8">{{ $customer->location->name }}</dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">{{ $customer->active ? 'Active' : 'Inactive' }}</dd>
            <dt class="col-sm-4">Marketing Consent</dt>
            <dd class="col-sm-8">{{ $customer->marketing_consent ? 'Yes' : 'No' }}</dd>
            <dt class="col-sm-4">Notes</dt>
            <dd class="col-sm-8">{{ $customer->notes ?? '-' }}</dd>
        </dl>
    </div>
    @can('update', $customer)
    <div class="card-footer">
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    </div>
    @endcan
</div>
@endsection
