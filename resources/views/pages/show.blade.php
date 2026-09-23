@extends('layouts.app')
@section('title', $page->title)
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-start justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1 text-break">{{ $page->title }}</h1>
        <small class="text-body-secondary">{{ $page->created_at->format('j M Y, g:ia') }}</small>
    </div>
    <div class="page-actions d-flex gap-2 d-print-none">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-print title="Print" aria-label="Print">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" class="align-text-bottom"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/></svg>
        </button>
        <a href="{{ route('pages.edit', $page) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
        <form method="POST" action="{{ route('pages.destroy', $page) }}" data-confirm="Delete this page?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
    </div>
</div>
{{-- renderedBody() escapes raw HTML; never use unescaped output for any other page field --}}
<article class="page-body bg-body p-3 rounded border">{!! $page->renderedBody() !!}</article>
@endsection
