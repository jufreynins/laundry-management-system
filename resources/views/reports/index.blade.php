@extends('layouts.app')

@section('title', 'Reports')
@section('header', 'Reports')

@section('content')
<form method="GET" class="d-flex gap-2 mb-3">
    <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
    <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
    <button class="btn btn-outline-secondary text-nowrap">Apply</button>
</form>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Sales by Service</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Service</th><th>Lines</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse ($salesByService as $row)
                        <tr>
                            <td>{{ $row['description'] }}</td>
                            <td>{{ $row['line_count'] }}</td>
                            <td class="text-end">${{ number_format($row['total'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No data for this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Payment Summary by Method</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Method</th><th>Count</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse ($paymentSummary as $row)
                        <tr>
                            <td>{{ \App\Enums\PaymentMethod::from($row['method'])->label() }}</td>
                            <td>{{ $row['payment_count'] }}</td>
                            <td class="text-end">${{ number_format($row['total'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No data for this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        @if (!empty($salesByLocation))
        <div class="card mb-3">
            <div class="card-header">Sales by Location</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Location</th><th>Orders</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach ($salesByLocation as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['order_count'] }}</td>
                            <td class="text-end">${{ number_format($row['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">Tax Summary</div>
            <div class="card-body">
                <p class="mb-0 fs-4">${{ number_format($taxSummary, 2) }}</p>
                <p class="text-muted small mb-0">Collected {{ $from->format('m/d/Y') }} &ndash; {{ $to->format('m/d/Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
