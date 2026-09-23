<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return PageResource::collection(
            $request->user()->pages()->latest('updated_at')->latest('id')->paginate(20)
        );
    }

    public function store(PageRequest $request): JsonResponse
    {
        $page = $request->user()->pages()->create($request->validated());

        return PageResource::make($page)->response()->setStatusCode(201);
    }

    public function show(Page $page): PageResource
    {
        Gate::authorize('view', $page);

        return PageResource::make($page);
    }

    public function update(PageRequest $request, Page $page): PageResource
    {
        Gate::authorize('update', $page);

        $page->update($request->validated());

        return PageResource::make($page);
    }

    public function destroy(Page $page): Response
    {
        Gate::authorize('delete', $page);

        $page->delete();

        return response()->noContent();
    }
}
