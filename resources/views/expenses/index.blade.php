@extends('layouts.app')

@section('title', 'Expenses')
@section('header', 'Expenses')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('create', App\Models\Expense::class)
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">Add Expense</a>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Date</th><th>Category</th><th>Supplier</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @forelse ($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('m/d/Y') }}</td>
                    <td>{{ $expense->category->name }}</td>
                    <td>{{ $expense->supplier?->name ?? '-' }}</td>
                    <td>{{ $expense->description }}</td>
                    <td class="text-end">${{ number_format($expense->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No expenses recorded</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $expenses->links() }}
</div>
@endsection
