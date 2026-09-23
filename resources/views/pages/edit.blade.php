@extends('layouts.app')
@section('title', 'Edit: '.$page->title)
@section('content')
<h1 class="h3 mb-3">Edit page</h1>
<form method="POST" action="{{ route('pages.update', $page) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input id="title" name="title" value="{{ old('title', $page->title) }}" class="form-control @error('title') is-invalid @enderror" required maxlength="255">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="body_markdown" class="form-label">Markdown</label>
        <textarea id="body_markdown" name="body_markdown" rows="20" class="form-control font-monospace @error('body_markdown') is-invalid @enderror" required>{{ old('body_markdown', $page->body_markdown) }}</textarea>
        @error('body_markdown')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('pages.show', $page) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
