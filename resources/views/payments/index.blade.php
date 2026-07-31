@extends('layouts.app')

@section('title', 'Payments')
@section('header', 'Payments')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
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
                    <td class="cell-mono">{{ $payment->payment_reference }}</td>
                    <td><a href="{{ route('orders.show', $payment->order) }}">{{ $payment->order->order_number }}</a></td>
                    <td>{{ $payment->method->label() }}</td>
                    <td><span class="status status-blue">{{ $payment->status->label() }}</span></td>
                    <td>${{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->recordedBy->name }}</td>
                    <td>{{ $payment->created_at->format('m/d/Y g:i A') }}</td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-title">No payments recorded</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</div>
@endsection
