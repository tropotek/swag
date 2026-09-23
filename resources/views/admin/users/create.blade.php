@extends('layouts.app')
@section('title', 'New user')
@section('content')
<div class="row"><div class="col-md-8 col-lg-6">
    <h1 class="h3 mb-3">New user</h1>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocapitalize="none">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Temporary password</label>
            <input id="password" type="text" name="password" class="form-control font-monospace @error('password') is-invalid @enderror" required autocomplete="off">
            <div class="form-text">They'll be asked to change it when they first log in.</div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-3">
            <input id="is_admin" type="checkbox" name="is_admin" value="1" class="form-check-input" @checked(old('is_admin'))>
            <label for="is_admin" class="form-check-label">Administrator</label>
        </div>
        <button type="submit" class="btn btn-primary">Create user</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div></div>
@endsection
