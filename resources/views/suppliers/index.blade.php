@extends('layouts.app')

@section('title', 'Suppliers')
@section('header', 'Suppliers')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('create', App\Models\Supplier::class)
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary">Add Supplier</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->contact_name ?? '-' }}</td>
                    <td>{{ $supplier->phone ?? '-' }}</td>
                    <td>{{ $supplier->email ?? '-' }}</td>
                    <td>{{ $supplier->active ? 'Active' : 'Inactive' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $suppliers->links() }}</div>
@endsection
