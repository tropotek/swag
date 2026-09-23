<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('lists only the user\'s own tokens', function () {
    $user = User::factory()->create();
    $user->createToken('My Claude');
    User::factory()->create()->createToken('Their ChatGPT');

    $this->actingAs($user)->get(route('tokens.index'))
        ->assertOk()
        ->assertSee('My Claude')
        ->assertDontSee('Their ChatGPT')
        ->assertSee('Never');
});

it('creates a token and flashes the plaintext once', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tokens.store'), ['name' => 'Claude'])
        ->assertRedirect(route('tokens.index'))
        ->assertSessionHas('plainTextToken');

    expect($user->tokens()->sole()->name)->toBe('Claude');
});

it('issues a token that works against the API', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tokens.store'), ['name' => 'Claude']);
    $plain = session('plainTextToken');
    $this->app['auth']->forgetGuards();

    $this->withToken($plain)->getJson('/api/pages')->assertOk();
});

it('shows a flashed token with a copy button', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['plainTextToken' => '1|abcdef'])
        ->get(route('tokens.index'))
        ->assertSee('1|abcdef')
        ->assertSee('data-copy-target', false);
});

it('requires a token name', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tokens.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('revokes the user\'s own token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Claude')->accessToken;

    $this->actingAs($user)->delete(route('tokens.destroy', $token->id))->assertRedirect(route('tokens.index'));

    expect(PersonalAccessToken::count())->toBe(0);
});

it('cannot revoke another user\'s token', function () {
    $token = User::factory()->create()->createToken('Theirs')->accessToken;

    $this->actingAs(User::factory()->create())->delete(route('tokens.destroy', $token->id))->assertNotFound();

    expect(PersonalAccessToken::count())->toBe(1);
});

it('returns 404 for a non-numeric token id', function () {
    $this->actingAs(User::factory()->create())->delete('/tokens/abc')->assertNotFound();
});

it('shows the Tokens link in the nav', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertSee(route('tokens.index'), false);
});
