@extends('layouts.app')
@section('title', 'API tokens')
@section('content')
<h1 class="h3 mb-3">API tokens</h1>

@if ($plainTextToken)
    <div class="alert alert-warning">
        <label for="new-token" class="form-label fw-semibold">Your new token</label>
        <div class="input-group">
            <input id="new-token" type="text" class="form-control font-monospace" value="{{ $plainTextToken }}" readonly>
            <button type="button" class="btn btn-outline-secondary" data-copy-target="#new-token">Copy</button>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('tokens.store') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-sm">
        <label for="name" class="visually-hidden">Token name</label>
        <input id="name" name="name" value="{{ old('name') }}" placeholder="Token name, e.g. Laptop agent"
               class="form-control @error('name') is-invalid @enderror" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-auto"><button type="submit" class="btn btn-primary w-100">Create token</button></div>
</form>

@if ($tokens->isEmpty())
    <p class="text-body-secondary">No tokens yet.</p>
@else
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Created</th><th>Last used</th><th></th></tr></thead>
            <tbody>
            @foreach ($tokens as $token)
                <tr>
                    <td class="text-break">{{ $token->name }}</td>
                    <td>{{ $token->created_at->format('j M Y') }}</td>
                    <td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('tokens.destroy', $token->id) }}"
                              data-confirm="Revoke this token? Any AI client using it will stop working.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Revoke</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<h2 class="h5 mt-4">Using a token</h2>
<p>Give your AI client the token and the API base <code>{{ url('/api') }}</code>. Endpoints:
    <code>GET/POST /pages</code>, <code>GET/PATCH/DELETE /pages/{id}</code>.</p>
<pre class="bg-body p-3 border rounded small"><code>curl -X POST {{ url('/api/pages') }} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title": "Hello", "body_markdown": "# Hello\n\nPosted by my AI."}'</code></pre>
@endsection
