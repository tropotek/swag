@extends('layouts.app')
@section('title', 'Change password')
@section('content')
<div class="row"><div class="col-md-8 col-lg-6">
    <h1 class="h3 mb-3">Change password</h1>
    @if ($forced)
        <div class="alert alert-info">Your password was set by an administrator. Choose a new one to continue.</div>
    @endif
    <form method="POST" action="{{ route('account.password.update') }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="current_password" class="form-label">Current password</label>
            <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Change password</button>
    </form>
</div></div>
@endsection
