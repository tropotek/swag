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
        $pages = $request->user()->pages()->latest('updated_at')->latest('id')->paginate(20);

        return view('pages.index', ['pages' => $pages]);
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
