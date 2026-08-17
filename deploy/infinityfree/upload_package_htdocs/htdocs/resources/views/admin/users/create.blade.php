@extends('layouts.app')

@section('page_title', 'Add User')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="mb-1">Add User</h2>
        <p class="text-muted mb-0">Create a new account for ViLoCare access.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            @include('admin.users.form', [
                'user' => null,
                'roles' => $roles,
                'submitLabel' => 'Save User',
                'passwordRequired' => true,
            ])
        </form>
    </div>
</div>

@endsection
