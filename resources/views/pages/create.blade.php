@extends('layouts.app')
@section('title', 'New page')
@section('content')
<h1 class="h3 mb-3">New page</h1>
<form method="POST" action="{{ route('pages.store') }}">
    @csrf
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input id="title" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" required maxlength="255" autofocus>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="body_markdown" class="form-label">Markdown</label>
        <div class="d-flex align-items-center gap-2 mb-2">
            <label for="media-file" class="btn btn-sm btn-outline-secondary mb-0">Attach file</label>
            <input id="media-file" type="file" multiple class="d-none">
            <span id="media-status" class="small text-body-secondary" role="status"></span>
        </div>
        <textarea id="body_markdown" name="body_markdown" rows="20" class="form-control font-monospace @error('body_markdown') is-invalid @enderror" required
                  data-media-url="{{ route('media.store') }}" data-csrf="{{ csrf_token() }}"
                  data-media-picker="#media-file" data-media-status="#media-status"
                  data-max-kb="{{ config('swag.media.max_kb') }}"
                  data-debug="{{ config('app.debug') ? '1' : '0' }}">{{ old('body_markdown') }}</textarea>
        <div class="form-text">Drop or paste files into the box to upload them. Images show in the page; audio and video play in a new tab; other files download.</div>
        @error('body_markdown')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Create</button>
        <a href="{{ route('home') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
@endsection
