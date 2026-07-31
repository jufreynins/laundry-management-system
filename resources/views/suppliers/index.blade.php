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
        <table class="table mb-0">
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                <tr>
                    <td class="cell-strong">{{ $supplier->name }}</td>
                    <td>{{ $supplier->contact_name ?? '-' }}</td>
                    <td>{{ $supplier->phone ?? '-' }}</td>
                    <td>{{ $supplier->email ?? '-' }}</td>
                    <td>
                        @if ($supplier->active)
                            <span class="status status-green">Active</span>
                        @else
                            <span class="status status-blue">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No suppliers yet</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $suppliers->links() }}
</div>
@endsection
