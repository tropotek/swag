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
    private const SORTS = [
        'updated' => 'Last updated',
        'created' => 'Newest created',
        'title' => 'Title A–Z',
    ];

    public function index(Request $request): View
    {
        $sort = $request->query('sort');
        $sort = is_string($sort) && isset(self::SORTS[$sort]) ? $sort : 'updated';

        $query = $request->user()->pages();
        $query = match ($sort) {
            'created' => $query->latest('created_at'),
            'title' => $query->orderByRaw('lower(title)'),
            default => $query->latest('updated_at'),
        };

        $pages = $query->latest('id')->paginate(20)->withQueryString();

        return view('pages.index', ['pages' => $pages, 'sort' => $sort, 'sorts' => self::SORTS]);
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
