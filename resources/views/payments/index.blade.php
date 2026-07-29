@extends('layouts.app')

@section('title', 'Payments')
@section('header', 'Payments')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Order</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Recorded By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_reference }}</td>
                    <td><a href="{{ route('orders.show', $payment->order) }}">{{ $payment->order->order_number }}</a></td>
                    <td>{{ $payment->method->label() }}</td>
                    <td><span class="badge bg-secondary status-badge">{{ $payment->status->label() }}</span></td>
                    <td>${{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->recordedBy->name }}</td>
                    <td>{{ $payment->created_at->format('m/d/Y g:i A') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No payments recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
