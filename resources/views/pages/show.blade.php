@extends('layouts.app')
@section('title', $page->title)
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-start justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1 text-break">{{ $page->title }}</h1>
        <small class="text-body-secondary">{{ $page->created_at->format('j M Y, g:ia') }}</small>
    </div>
    <div class="d-flex gap-2">
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
