<?php

use App\Models\Page;
use App\Models\User;

it('lists only the user\'s own pages, newest first', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'Older page', 'created_at' => now()->subDay()]);
    Page::factory()->for($user)->create(['title' => 'Newer page']);
    Page::factory()->create(['title' => 'Someone else page']);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Newer page', 'Older page'])
        ->assertDontSee('Someone else page');
});

it('shows an empty state', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertSee('No pages yet');
});

it('paginates the feed at 20', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'The very first page', 'created_at' => now()->subYear()]);
    Page::factory()->for($user)->count(20)->create();

    $this->actingAs($user)->get('/')->assertDontSee('The very first page');
    $this->actingAs($user)->get('/?page=2')->assertSee('The very first page');
});

it('escapes html in titles', function () {
    $page = Page::factory()->create(['title' => '<script>alert(1)</script>']);

    $this->actingAs($page->user)->get('/')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);

    $this->actingAs($page->user)->get(route('pages.show', $page))
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('shows a page with rendered markdown', function () {
    $page = Page::factory()->create(['body_markdown' => 'some **bold** text']);

    $this->actingAs($page->user)->get(route('pages.show', $page))
        ->assertOk()
        ->assertSee('<strong>bold</strong>', false);
});

it('returns 404 for another user\'s page on every route', function () {
    $page = Page::factory()->create(['title' => 'Original']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get(route('pages.show', $page))->assertNotFound();
    $this->actingAs($intruder)->get(route('pages.edit', $page))->assertNotFound();
    $this->actingAs($intruder)->put(route('pages.update', $page), ['title' => 'Hacked', 'body_markdown' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->delete(route('pages.destroy', $page))->assertNotFound();

    expect($page->fresh()->title)->toBe('Original');
});

it('returns 404 for a page that does not exist', function () {
    $this->actingAs(User::factory()->create())->get('/pages/999')->assertNotFound();
});

it('updates a page', function () {
    $page = Page::factory()->create();

    $this->actingAs($page->user)
        ->put(route('pages.update', $page), ['title' => 'New title', 'body_markdown' => 'New body'])
        ->assertRedirect(route('pages.show', $page));

    expect($page->fresh()->only('title', 'body_markdown'))->toBe(['title' => 'New title', 'body_markdown' => 'New body']);
});

it('requires a title when updating', function () {
    $page = Page::factory()->create();

    $this->actingAs($page->user)
        ->put(route('pages.update', $page), ['title' => '', 'body_markdown' => 'x'])
        ->assertSessionHasErrors('title');
});

it('deletes a page', function () {
    $page = Page::factory()->create();

    $this->actingAs($page->user)->delete(route('pages.destroy', $page))->assertRedirect(route('home'));

    expect(Page::count())->toBe(0);
});
