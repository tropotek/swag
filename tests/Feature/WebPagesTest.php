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

it('puts the most recently updated page first', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'Old but just edited', 'created_at' => now()->subMonth(), 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'New but untouched', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    $this->actingAs($user)->get('/')->assertSeeInOrder(['Old but just edited', 'New but untouched']);
});

it('sorts by title A to Z', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'Zebra fencing', 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'apple trees', 'updated_at' => now()->subDay()]);

    $this->actingAs($user)->get('/?sort=title')->assertSeeInOrder(['apple trees', 'Zebra fencing']);
});

it('sorts by newest created', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'Old but just edited', 'created_at' => now()->subMonth(), 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'New but untouched', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    $this->actingAs($user)->get('/?sort=created')->assertSeeInOrder(['New but untouched', 'Old but just edited']);
});

it('falls back to last updated for an unknown sort', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'Old but just edited', 'created_at' => now()->subMonth(), 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'New but untouched', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    $this->actingAs($user)->get('/?sort=nonsense')->assertOk()->assertSeeInOrder(['Old but just edited', 'New but untouched']);
});

it('ignores a malformed sort parameter', function () {
    $this->actingAs(User::factory()->create())->get('/?sort[]=title')->assertOk();
});

it('shows the sort dropdown with the current choice and keeps it in page links', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->count(51)->create();

    $this->actingAs($user)->get('/?sort=title')
        ->assertSee('name="sort"', false)
        ->assertSee('<option value="title" selected>', false)
        ->assertSee('sort=title&amp;page=2', false);
});

it('shows an empty state', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertSee('No pages yet');
});

it('paginates the feed at 50 by default', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'The very first page', 'created_at' => now()->subYear()]);
    Page::factory()->for($user)->count(50)->create();

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

it('offers a print button and hides page chrome when printing', function () {
    $page = Page::factory()->create();

    $this->actingAs($page->user)->get(route('pages.show', $page))
        ->assertSee('data-print', false)
        ->assertSee('navbar navbar-expand-md bg-body border-bottom mb-4 d-print-none', false)
        ->assertSee('page-actions d-flex gap-2 d-print-none', false);
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

it('lets the user pick a page size and keeps it in page links', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->count(25)->create();

    $this->actingAs($user)->get('/?per_page=20')
        ->assertSee('name="per_page"', false)
        ->assertSee('<option value="20" selected>', false)
        ->assertSee('per_page=20&amp;page=2', false);
    $this->actingAs($user)->get('/')->assertSee('<option value="50" selected>', false);
    $this->actingAs($user)->get('/?per_page=7')->assertSee('<option value="50" selected>', false);
    $this->actingAs($user)->get('/?per_page[]=7')->assertOk();
});

it('searches from the nav box and lists matching pages', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->create(['title' => 'Tap repair guide']);
    Page::factory()->for($user)->create(['title' => 'Gardening']);
    Page::factory()->create(['title' => 'Tap repair for someone else']);

    $this->actingAs($user)->get('/?q=tap')
        ->assertSee('name="q"', false)
        ->assertSee('Tap repair guide')
        ->assertDontSee('Gardening')
        ->assertDontSee('someone else')
        ->assertSee('Best match');
});

it('puts the search box before the theme toggle', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertSeeInOrder(['name="q"', 'id="theme-toggle"'], false);
});

it('keeps the search term in page links and says when nothing matches', function () {
    $user = User::factory()->create();
    Page::factory()->for($user)->count(21)->create(['title' => 'Tap thing']);

    $this->actingAs($user)->get('/?q=tap&per_page=20')->assertSee('q=tap&amp;per_page=20&amp;page=2', false);
    $this->actingAs($user)->get('/?q=nomatchatall')->assertSee('No pages match');
});

it('escapes the search term', function () {
    $this->actingAs(User::factory()->create())->get('/?q='.urlencode('"><script>alert(1)</script>'))
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('wires the edit page for media upload', function () {
    $user = User::factory()->create();
    $page = Page::factory()->for($user)->create();

    $this->actingAs($user)->get(route('pages.edit', $page))
        ->assertOk()
        ->assertSee('data-media-url="'.route('media.store').'"', false)
        ->assertSee('id="media-file"', false)
        ->assertSee('id="media-status"', false);
});
