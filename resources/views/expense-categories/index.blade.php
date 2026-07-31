@extends('layouts.app')

@section('title', 'Expense Categories')
@section('header', 'Expense Categories')

@section('content')
<div class="row col-2">
    <div class="card">
        <ul class="list-group">
            @forelse ($categories as $category)
            <li class="list-group-item">{{ $category->name }}</li>
            @empty
            <li class="list-group-item text-muted small">No categories yet.</li>
            @endforelse
        </ul>
    </div>
    @if (auth()->user()->role === \App\Enums\UserRole::OWNER)
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('expense-categories.store') }}">
                @csrf
                <div class="form-group mb-2">
                    <input type="text" name="name" class="form-control" placeholder="New category name" required>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Add Category</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
