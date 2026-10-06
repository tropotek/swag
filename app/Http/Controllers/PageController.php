<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $term = Page::searchTerm($request->query('q'));
        $searching = $term !== '';
        $sort = Page::sortKey($request->query('sort'), $searching);
        $perPage = Page::perPage($request->query('per_page'));

        $pages = $request->user()->pages()
            ->search($term)
            ->sorted($sort)
            ->paginate($perPage)
            ->withQueryString();

        return view('pages.index', [
            'pages' => $pages,
            'sort' => $sort,
            'sorts' => $searching ? [Page::RELEVANCE => 'Best match'] + Page::SORTS : Page::SORTS,
            'perPage' => $perPage,
            'perPageOptions' => Page::PER_PAGE_OPTIONS,
            'term' => $term,
        ]);
    }

    public function show(Page $page): View
    {
        Gate::authorize('view', $page);

        return view('pages.show', ['page' => $page]);
    }

    public function edit(Page $page): View
    {
        Gate::authorize('update', $page);

        return view('pages.edit', ['page' => $page]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        Gate::authorize('update', $page);

        $page->update($request->validated());

        return redirect()->route('pages.show', $page)->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        Gate::authorize('delete', $page);

        $page->delete();

        return redirect()->route('home')->with('status', 'Page deleted.');
    }
}
