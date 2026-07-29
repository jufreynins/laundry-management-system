@extends('layouts.app')

@section('title', 'Audit Logs')
@section('header', 'Audit Logs')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Model</th>
                    <th>Location</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('m/d/Y g:i A') }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td><span class="badge bg-secondary status-badge">{{ $log->action }}</span></td>
                    <td>{{ $log->model }} #{{ $log->model_id }}</td>
                    <td>{{ $log->location?->name ?? '-' }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No audit log entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
