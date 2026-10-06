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
<p>The API describes itself with an OpenAPI document listing every operation, parameter, limit and error shape.
    Point your AI client at <a href="{{ url('/api/openapi.json') }}"><code>{{ url('/api/openapi.json') }}</code></a>
    (no token needed) and it can work out how to use the API.</p>
<pre class="bg-body p-3 border rounded small"><code>curl -X POST {{ url('/api/pages') }} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title": "Hello", "body_markdown": "# Hello\n\nPosted by my AI."}'</code></pre>

<h2 class="h5 mt-4">Claude Code skill</h2>
<p>Copy this into <code>~/.claude/skills/swag/SKILL.md</code> to let Claude Code add to and search your swag.
    It needs the <code>SWAG_URL</code> and <code>SWAG_TOKEN</code> environment variables set.</p>
<div class="mb-2"><button type="button" class="btn btn-outline-secondary btn-sm" data-copy-target="#skill-text">Copy</button></div>
<textarea id="skill-text" class="form-control font-monospace small" rows="16" readonly>{{ $skill }}</textarea>
@endsection
