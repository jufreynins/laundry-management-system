@extends('layouts.app')

@section('title', 'Users')
@section('header', 'Users')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                <tr>
                    <td class="cell-strong"><a href="{{ route('admin.users.show', $u) }}">{{ $u->name }}</a></td>
                    <td>{{ $u->email }}</td>
                    <td><span class="status status-blue">{{ $u->role->label() }}</span></td>
                    <td>{{ $u->location?->name ?? 'All Locations' }}</td>
                    <td>
                        @if ($u->active)
                            <span class="status status-green">Active</span>
                        @else
                            <span class="status status-red">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $u->last_login_at?->format('m/d/Y g:i A') ?? 'Never' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
