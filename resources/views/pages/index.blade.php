@extends('layouts.app')
@section('title', 'Pages')
@section('content')
<h1 class="h3 mb-3">Pages</h1>
@if ($pages->isEmpty())
    <p class="text-body-secondary">No pages yet. Pages posted through the API will appear here.</p>
@else
    <div class="list-group mb-3">
        @foreach ($pages as $page)
            <a href="{{ route('pages.show', $page) }}" class="list-group-item list-group-item-action">
                <div class="fw-semibold text-break">{{ $page->title }}</div>
                <small class="text-body-secondary">{{ $page->created_at->format('j M Y, g:ia') }}</small>
            </a>
        @endforeach
    </div>
    {{ $pages->links() }}
@endif
@endsection
