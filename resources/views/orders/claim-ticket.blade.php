<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Claim Ticket {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #111; max-width: 400px; margin: 2rem auto; border: 2px dashed #333; padding: 1rem; }
        h1 { font-size: 20px; text-align: center; }
        .big { font-size: 28px; font-weight: bold; text-align: center; letter-spacing: 2px; }
        dl { display: grid; grid-template-columns: auto 1fr; gap: 0.25rem 1rem; }
        dt { font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $order->location->name }}</h1>
    <p class="big">{{ $order->order_number }}</p>

    <dl>
        <dt>Customer</dt><dd>{{ $order->customer->name }}</dd>
        <dt>Items</dt><dd>{{ $order->item_count ?? '-' }}</dd>
        <dt>Bags</dt><dd>{{ $order->bag_count ?? '-' }}</dd>
        <dt>Weight</dt><dd>{{ $order->weight_lbs ?? '-' }} lbs</dd>
        <dt>Promised</dt><dd>{{ $order->promised_at?->format('m/d/Y g:i A') ?? '-' }}</dd>
        <dt>Rush</dt><dd>{{ $order->rush ? 'Yes' : 'No' }}</dd>
    </dl>

    <p style="margin-top: 1rem; font-size: 12px; text-align: center;">Present this ticket to claim your order.</p>
</body>
</html>
