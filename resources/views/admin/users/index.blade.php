@extends('layouts.app')

@section('title', 'Users')
@section('header', 'Users')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
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
                    <td><a href="{{ route('admin.users.show', $u) }}">{{ $u->name }}</a></td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->role->label() }}</td>
                    <td>{{ $u->location?->name ?? 'All Locations' }}</td>
                    <td>{{ $u->active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $u->last_login_at?->format('m/d/Y g:i A') ?? 'Never' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
