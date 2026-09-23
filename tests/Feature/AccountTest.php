<?php

use App\Models\User;

it('updates name and lowercases the email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('account.update'), ['name' => 'Ada L', 'email' => 'Ada.L@Example.com'])
        ->assertRedirect(route('account.edit'));

    $user->refresh();
    expect($user->name)->toBe('Ada L')->and($user->email)->toBe('ada.l@example.com');
});

it('rejects an email already used by someone else, ignoring case', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('account.update'), ['name' => 'X', 'email' => 'TAKEN@example.com'])
        ->assertSessionHasErrors('email');
});

it('allows keeping your own email', function () {
    $user = User::factory()->create(['email' => 'me@example.com']);

    $this->actingAs($user)->put(route('account.update'), ['name' => 'Me', 'email' => 'me@example.com'])
        ->assertSessionHasNoErrors();
});
