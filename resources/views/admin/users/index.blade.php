@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Users</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">New user</a>
</div>
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Created</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td class="text-break">
                    {{ $user->name }}
                    @if ($user->is_admin)<span class="badge text-bg-primary">Admin</span>@endif
                    @if ($user->must_change_password)<span class="badge text-bg-warning">Temp password</span>@endif
                </td>
                <td class="text-break">{{ $user->email }}</td>
                <td>{{ $user->created_at->format('j M Y') }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('admin.users.password.edit', $user) }}" class="btn btn-outline-secondary btn-sm">Set password</a>
                    @unless ($user->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                              data-confirm="Delete {{ $user->email }} and all their pages and tokens?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
