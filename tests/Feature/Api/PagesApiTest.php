<?php

use App\Models\Page;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns JSON 401 without an Accept header', function () {
    $this->get('/api/pages')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('authenticates with a real bearer token and rejects it once revoked', function () {
    $user = User::factory()->create();
    $token = $user->createToken('claude');

    $this->withToken($token->plainTextToken)->getJson('/api/pages')->assertOk();

    $token->accessToken->delete();
    $this->app['auth']->forgetGuards();

    $this->withToken($token->plainTextToken)->getJson('/api/pages')->assertUnauthorized();
});

it('creates a page owned by the token user', function () {
    $user = Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/pages', ['title' => 'Hello', 'body_markdown' => '# Hi'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Hello')
        ->assertJsonPath('data.body_markdown', '# Hi');

    $page = Page::sole();
    $response->assertJsonPath('data.url', route('pages.show', $page));
    expect($page->user_id)->toBe($user->id);
});

it('returns JSON 422 without an Accept header', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->post('/api/pages', ['body_markdown' => 'no title'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');
});

it('rejects an oversized body', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/pages', ['title' => 'Big', 'body_markdown' => str_repeat('a', 1_000_001)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('body_markdown');

    expect(Page::count())->toBe(0);
});

it('rejects a title over 255 characters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/pages', ['title' => str_repeat('t', 256), 'body_markdown' => 'x'])
        ->assertJsonValidationErrors('title');
});

it('lists only the caller\'s pages, newest first, 50 per page', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->count(50)->create(['created_at' => now()->subDay()]);
    Page::factory()->for($user)->create(['title' => 'Newest']);
    Page::factory()->create();

    $this->getJson('/api/pages')
        ->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('data.0.title', 'Newest')
        ->assertJsonPath('meta.total', 51);
});

it('lists the most recently updated page first', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->create(['title' => 'Old but just edited', 'created_at' => now()->subMonth(), 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'New but untouched', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    $this->getJson('/api/pages')->assertJsonPath('data.0.title', 'Old but just edited');
});

it('sorts by title A to Z', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->create(['title' => 'Zebra fencing', 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'apple trees', 'updated_at' => now()->subDay()]);

    $this->getJson('/api/pages?sort=title')->assertJsonPath('data.0.title', 'apple trees');
});

it('sorts by newest created', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->create(['title' => 'Old but just edited', 'created_at' => now()->subMonth(), 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'New but untouched', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    $this->getJson('/api/pages?sort=created')->assertJsonPath('data.0.title', 'New but untouched');
});

it('keeps the sort in pagination links and ignores a malformed sort', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->count(51)->create();

    expect($this->getJson('/api/pages?sort=title')->json('links.next'))->toContain('sort=title');
    $this->getJson('/api/pages?sort[]=title')->assertOk();
});

it('honours per_page and falls back to 50 for unsupported values', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->count(51)->create();

    $this->getJson('/api/pages?per_page=20')->assertJsonCount(20, 'data')->assertJsonPath('meta.per_page', 20);
    $this->getJson('/api/pages?per_page=7')->assertJsonCount(50, 'data');
    $this->getJson('/api/pages?per_page[]=20')->assertOk()->assertJsonCount(50, 'data');
    expect($this->getJson('/api/pages?per_page=20')->json('links.next'))->toContain('per_page=20');
});

it('searches titles and bodies of the caller\'s pages only', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->create(['title' => 'Tap repair guide', 'body_markdown' => 'washers']);
    Page::factory()->for($user)->create(['title' => 'Unrelated', 'body_markdown' => 'Replace the washer first']);
    Page::factory()->for($user)->create(['title' => 'Gardening', 'body_markdown' => 'roses']);
    Page::factory()->create(['title' => 'Tap repair for someone else']);

    $this->getJson('/api/pages?q=tap')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Tap repair guide');
    $this->getJson('/api/pages?q=washer')->assertJsonCount(2, 'data');
    expect($this->getJson('/api/pages?q=washer&per_page=20')->json('meta.total'))->toBe(2);
});

it('ranks title matches above body matches by default when searching', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->create(['title' => 'Notes', 'body_markdown' => 'about tap fittings', 'updated_at' => now()]);
    Page::factory()->for($user)->create(['title' => 'Tap guide', 'body_markdown' => 'misc', 'updated_at' => now()->subDay()]);

    $this->getJson('/api/pages?q=tap')->assertJsonPath('data.0.title', 'Tap guide');
    $this->getJson('/api/pages?q=tap&sort=updated')->assertJsonPath('data.0.title', 'Notes');
});

it('survives hostile search input', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->create(['title' => 'Tap guide']);

    foreach (['"', 'tap AND', 'NEAR(', '*', 'title:tap', "tap'; drop table pages;--", '   ', '(((', '-tap'] as $q) {
        $this->getJson('/api/pages?'.http_build_query(['q' => $q]))->assertOk();
    }
    $this->getJson('/api/pages?q[]=tap')->assertOk()->assertJsonCount(1, 'data');
});

it('finds pages after they are updated and not after they are deleted', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->for($user)->create(['title' => 'Before', 'body_markdown' => 'x']);

    $this->patchJson("/api/pages/{$page->id}", ['title' => 'Zebra'])->assertOk();
    $this->getJson('/api/pages?q=zebra')->assertJsonCount(1, 'data');
    $this->getJson('/api/pages?q=before')->assertJsonCount(0, 'data');

    $this->deleteJson("/api/pages/{$page->id}")->assertNoContent();
    $this->getJson('/api/pages?q=zebra')->assertJsonCount(0, 'data');
});

it('shows one of the caller\'s pages', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->for($user)->create();

    $this->getJson("/api/pages/{$page->id}")->assertOk()->assertJsonPath('data.id', $page->id);
});

it('returns JSON 404 without an Accept header', function () {
    Sanctum::actingAs(User::factory()->create());
    $other = Page::factory()->create();

    $this->get("/api/pages/{$other->id}")
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');

    $this->get('/api/pages/999999')->assertNotFound()->assertHeader('Content-Type', 'application/json');
});

it('patches only the fields sent', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->for($user)->create(['title' => 'Keep me']);

    $this->patchJson("/api/pages/{$page->id}", ['body_markdown' => 'new body'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Keep me')
        ->assertJsonPath('data.body_markdown', 'new body');
});

it('rejects an empty title on update', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->for($user)->create();

    $this->putJson("/api/pages/{$page->id}", ['title' => ''])->assertJsonValidationErrors('title');
});

it('cannot update or delete another user\'s page', function () {
    Sanctum::actingAs(User::factory()->create());
    $other = Page::factory()->create(['title' => 'Theirs']);

    $this->patchJson("/api/pages/{$other->id}", ['title' => 'Mine now'])->assertNotFound();
    $this->deleteJson("/api/pages/{$other->id}")->assertNotFound();

    expect($other->fresh()->title)->toBe('Theirs');
});

it('deletes a page', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->for($user)->create();

    $this->deleteJson("/api/pages/{$page->id}")->assertNoContent();

    expect(Page::count())->toBe(0);
});
