@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <h1 class="h3 mb-3">Log in</h1>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       required autofocus autocomplete="username" autocapitalize="none">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="form-check mb-3">
                <input id="remember" type="checkbox" name="remember" value="1" class="form-check-input">
                <label for="remember" class="form-check-label">Keep me logged in</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log in</button>
        </form>
    </div>
</div>
@endsection
