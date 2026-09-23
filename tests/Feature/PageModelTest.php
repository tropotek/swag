<?php

use App\Models\Page;
use App\Models\User;

it('renders markdown to html', function () {
    $html = Page::factory()->make(['body_markdown' => "# Hi\n\n**bold**"])->renderedBody();

    expect($html)->toContain('<h1>Hi</h1>')->toContain('<strong>bold</strong>');
});

it('renders GitHub-style tables', function () {
    $html = Page::factory()->make(['body_markdown' => "| a | b |\n|---|---|\n| 1 | 2 |"])->renderedBody();

    expect($html)->toContain('<table>');
});

it('escapes raw html', function () {
    $html = Page::factory()->make([
        'body_markdown' => "<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>",
    ])->renderedBody();

    expect($html)->not->toContain('<script')
        ->not->toContain('<img')
        ->toContain('&lt;script&gt;');
});

it('drops unsafe link targets', function () {
    $html = Page::factory()->make([
        'body_markdown' => "[a](javascript:alert(1)) [b](JaVaScRiPt:alert(1)) [c](data:text/html,hi)",
    ])->renderedBody();

    expect(strtolower($html))->not->toContain('javascript:')->not->toContain('data:text');
});

it('renders deeply nested hostile markdown without exhausting memory', function () {
    foreach ([str_repeat('>', 50000), str_repeat('- ', 50000)] as $body) {
        $html = Page::factory()->make(['body_markdown' => $body])->renderedBody();

        expect($html)->toBeString();
    }
});

it('belongs to a user who has many pages', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->count(2)->create();

    expect($user->pages)->toHaveCount(2)
        ->and($user->pages->first()->user->is($user))->toBeTrue();
});

it('deletes pages when their user is deleted', function () {
    $page = Page::factory()->create();

    $page->user->delete();

    expect(Page::count())->toBe(0);
});
