@extends('layouts.app')
@section('title', 'Pages')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0">
        @if ($term !== '')
            Results for “{{ $term }}” <a href="{{ route('home') }}" class="fs-6 ms-1">Clear</a>
        @else
            Pages
        @endif
    </h1>
    <form method="GET" action="{{ route('home') }}" class="d-flex gap-2">
        @if ($term !== '')
            <input type="hidden" name="q" value="{{ $term }}">
        @endif
        <label for="sort" class="visually-hidden">Sort by</label>
        <select id="sort" name="sort" class="form-select form-select-sm" data-autosubmit>
            @foreach ($sorts as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="per_page" class="visually-hidden">Pages per screen</label>
        <select id="per_page" name="per_page" class="form-select form-select-sm" data-autosubmit>
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-sm btn-outline-secondary">Apply</button></noscript>
    </form>
</div>
@if ($pages->isEmpty())
    <p class="text-body-secondary">
        @if ($term !== '')
            No pages match “{{ $term }}”.
        @else
            No pages yet. Pages posted through the API will appear here.
        @endif
    </p>
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
