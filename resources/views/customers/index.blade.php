@extends('layouts.app')

@section('title', 'Customers')
@section('header', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name, phone, email, or customer #">
        <button class="btn btn-outline">Search</button>
    </form>
    @can('create', App\Models\Customer::class)
    <a href="{{ route('customers.create') }}" class="btn btn-primary">Add Customer</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
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
                    <td class="cell-mono"><a href="{{ route('customers.show', $customer) }}">{{ $customer->customer_number }}</a></td>
                    <td class="cell-strong">{{ $customer->name }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->location->name }}</td>
                    <td>
                        @if ($customer->active)
                            <span class="status status-green">Active</span>
                        @else
                            <span class="status status-blue">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No customers found</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links() }}
</div>
@endsection
