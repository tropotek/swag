@extends('layouts.app')
@section('title', 'Account')
@section('content')
<div class="row"><div class="col-md-8 col-lg-6">
    <h1 class="h3 mb-3">Account</h1>
    <form method="POST" action="{{ route('account.update') }}" class="mb-4">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required autocapitalize="none">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
    <a href="{{ route('account.password.edit') }}">Change password</a>
</div></div>
@endsection
