<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Checkout (Simulated)</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-light">
    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-sm" style="width: 100%; max-width: 420px;">
            <div class="card-body">
                <h5 class="text-center mb-1">Simulated Hosted Checkout</h5>
                <p class="text-center text-muted small mb-4">
                    This stands in for a real PCI-compliant hosted checkout page.
                    No card details are collected by this application.
                </p>

                <p class="text-center">Amount due: <strong>${{ number_format($payment->amount, 2) }}</strong></p>

                <form method="POST" action="{{ route('webhooks.payments') }}" class="mb-2">
                    <input type="hidden" name="payload" value="{{ $successPayload }}">
                    <input type="hidden" name="signature" value="{{ $successSignature }}">
                    <button type="submit" class="btn btn-success w-100">Simulate Successful Payment</button>
                </form>
                <form method="POST" action="{{ route('webhooks.payments') }}">
                    <input type="hidden" name="payload" value="{{ $failedPayload }}">
                    <input type="hidden" name="signature" value="{{ $failedSignature }}">
                    <button type="submit" class="btn btn-outline-danger w-100">Simulate Failed Payment</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
