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

it('lists only the caller\'s pages, newest first, 20 per page', function () {
    $user = Sanctum::actingAs(User::factory()->create());
    Page::factory()->for($user)->count(20)->create(['created_at' => now()->subDay()]);
    Page::factory()->for($user)->create(['title' => 'Newest']);
    Page::factory()->create();

    $this->getJson('/api/pages')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('data.0.title', 'Newest')
        ->assertJsonPath('meta.total', 21);
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
