@extends('layouts.app')

@section('title', 'Customers')
@section('header', 'Customers')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name, phone, email, or customer #">
        <button class="btn btn-outline-secondary">Search</button>
    </form>
    @can('create', App\Models\Customer::class)
    <a href="{{ route('customers.create') }}" class="btn btn-primary">Add Customer</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Customer #</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                <tr>
                    <td><a href="{{ route('customers.show', $customer) }}">{{ $customer->customer_number }}</a></td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->location->name }}</td>
                    <td>
                        @if ($customer->active)
                            <span class="badge bg-success status-badge">Active</span>
                        @else
                            <span class="badge bg-secondary status-badge">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $customers->links() }}</div>
@endsection
