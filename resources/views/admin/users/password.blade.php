@extends('layouts.app')
@section('title', 'Set password')
@section('content')
<div class="row"><div class="col-md-8 col-lg-6">
    <h1 class="h3 mb-3">Set a temporary password for {{ $user->email }}</h1>
    <form method="POST" action="{{ route('admin.users.password.update', $user) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="password" class="form-label">Temporary password</label>
            <input id="password" type="text" name="password" class="form-control font-monospace @error('password') is-invalid @enderror" required autocomplete="off">
            <div class="form-text">They'll be asked to change it at their next login.</div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Set password</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div></div>
@endsection
