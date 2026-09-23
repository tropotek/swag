@extends('layouts.app')
@section('title', 'Pages')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0">Pages</h1>
    <form method="GET" action="{{ route('home') }}">
        <label for="sort" class="visually-hidden">Sort by</label>
        <select id="sort" name="sort" class="form-select form-select-sm" data-autosubmit>
            @foreach ($sorts as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-sm btn-outline-secondary mt-1">Sort</button></noscript>
    </form>
</div>
@if ($pages->isEmpty())
    <p class="text-body-secondary">No pages yet. Pages posted through the API will appear here.</p>
@else
    <div class="list-group mb-3">
        @foreach ($pages as $page)
            <a href="{{ route('pages.show', $page) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-baseline gap-3">
                <span class="fw-semibold text-break">{{ $page->title }}</span>
                <small class="text-body-secondary text-nowrap" title="Updated {{ $page->updated_at->format('j M Y, g:ia') }}">{{ $page->updated_at->format('j M Y') }}</small>
            </a>
        @endforeach
    </div>
    {{ $pages->links() }}
@endif
@endsection
