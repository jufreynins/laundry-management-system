@extends('layouts.app')

@section('title', 'Audit Logs')
@section('header', 'Audit Logs')

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
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
                    <td><span class="status status-blue">{{ $log->action }}</span></td>
                    <td class="cell-mono">{{ $log->model }} #{{ $log->model_id }}</td>
                    <td>{{ $log->location?->name ?? '-' }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No audit log entries yet</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
