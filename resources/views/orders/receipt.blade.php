<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #111; max-width: 500px; margin: 2rem auto; }
        h1 { font-size: 18px; margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { text-align: left; padding: 4px 0; border-bottom: 1px solid #eee; }
        .totals td { border: none; padding: 2px 0; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <h1>{{ $order->location->name }}</h1>
    <p>{{ $order->location->address }}, {{ $order->location->city }}, {{ $order->location->state }} {{ $order->location->zip }}<br>
    {{ $order->location->phone }}</p>

    <p><strong>Receipt for Order {{ $order->order_number }}</strong><br>
    Customer: {{ $order->customer->name }}<br>
    Date: {{ $order->intake_at->format('m/d/Y g:i A') }}</p>

    <table>
        <thead><tr><th>Item</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="text-end">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-end">${{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td>Discount</td><td class="text-end">-${{ number_format($order->discount_amount, 2) }}</td></tr>
        <tr><td>Tax</td><td class="text-end">${{ number_format($order->tax_amount, 2) }}</td></tr>
        <tr><td>Tip</td><td class="text-end">${{ number_format($order->tip_amount, 2) }}</td></tr>
        <tr class="fw-bold"><td>Total</td><td class="text-end">${{ number_format($order->total, 2) }}</td></tr>
        <tr><td>Amount Paid</td><td class="text-end">${{ number_format($order->amount_paid, 2) }}</td></tr>
        <tr class="fw-bold"><td>Balance Due</td><td class="text-end">${{ number_format($order->balance_due, 2) }}</td></tr>
    </table>

    @if ($order->payments->isNotEmpty())
    <p><strong>Payments</strong></p>
    <table>
        <thead><tr><th>Reference</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
            @foreach ($order->payments as $payment)
            <tr>
                <td>{{ $payment->payment_reference }}</td>
                <td>{{ $payment->method->label() }}</td>
                <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 2rem; font-size: 12px; color: #666;">Thank you for your business.</p>
</body>
</html>
