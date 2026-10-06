<?php

use App\Models\Page;
use Illuminate\Support\Facades\Route;

it('serves the OpenAPI document without a token', function () {
    $this->getJson('/api/openapi.json')
        ->assertOk()
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonPath('servers.0.url', url('/api'))
        ->assertJsonStructure(['info', 'components' => ['securitySchemes', 'schemas'], 'paths']);
});

it('serves the document even without an Accept header', function () {
    $this->get('/api/openapi.json')->assertOk()->assertHeader('Content-Type', 'application/json');
});

it('documents every API route and nothing else', function () {
    $documented = collect($this->getJson('/api/openapi.json')->json('paths'))
        ->flatMap(fn (array $methods, string $path) => collect(array_keys($methods))
            ->reject(fn (string $key) => $key === 'parameters')
            ->map(fn (string $method) => strtoupper($method).' /api'.preg_replace('/\{\w+\}/', '{}', $path)));

    $registered = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/') && $route->uri() !== 'api/openapi.json')
        ->flatMap(fn ($route) => collect($route->methods())
            ->reject(fn (string $method) => in_array($method, ['HEAD', 'PUT'], true))
            ->map(fn (string $method) => $method.' /'.preg_replace('/\{\w+\}/', '{}', $route->uri())));

    expect($documented->sort()->values()->all())->toBe($registered->sort()->values()->all());
});

it('documents the list parameters the controller accepts', function () {
    $params = collect($this->getJson('/api/openapi.json')->json('paths./pages.get.parameters'))->keyBy('name');

    expect($params->keys()->sort()->values()->all())->toBe(['page', 'per_page', 'q', 'sort'])
        ->and($params['per_page']['schema']['enum'])->toBe(Page::PER_PAGE_OPTIONS)
        ->and($params['per_page']['schema']['default'])->toBe(Page::DEFAULT_PER_PAGE)
        ->and($params['sort']['schema']['enum'])->toBe([...array_keys(Page::SORTS), Page::RELEVANCE]);
});
