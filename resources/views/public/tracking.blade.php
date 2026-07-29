<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Your Order — {{ $orderNumber }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-light">
    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-sm" style="width: 100%; max-width: 420px;">
            <div class="card-body">
                <h5 class="text-center mb-1">{{ $storeName }}</h5>
                <p class="text-center text-muted small mb-4">{{ $storePhone }}</p>

                <dl class="row mb-0">
                    <dt class="col-sm-5">Order Number</dt>
                    <dd class="col-sm-7">{{ $orderNumber }}</dd>
                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7"><span class="badge bg-info status-badge">{{ $status }}</span></dd>
                    @if ($promisedAt)
                    <dt class="col-sm-5">Promised</dt>
                    <dd class="col-sm-7">{{ $promisedAt->format('m/d/Y g:i A') }}</dd>
                    @endif
                    @if ($deliveryType)
                    <dt class="col-sm-5">{{ $deliveryType }}</dt>
                    <dd class="col-sm-7">{{ $deliveryStatus }}</dd>
                    @endif
                    <dt class="col-sm-5">Amount Due</dt>
                    <dd class="col-sm-7">${{ number_format($balanceDue, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</body>
</html>
