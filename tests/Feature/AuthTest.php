<?php

use App\Models\User;

it('shows the login page to guests', function () {
    $this->get('/login')->assertOk()->assertSee('Log in');
});

it('redirects guests from the home page to login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('logs in with a capitalised email', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'correct-horse-battery']);

    $this->post('/login', ['email' => 'Ada@Example.com', 'password' => 'correct-horse-battery'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'nope'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('throttles repeated login attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => 'x@example.com', 'password' => 'nope']);
    }

    $this->post('/login', ['email' => 'x@example.com', 'password' => 'nope'])->assertStatus(429);
});

it('logs out', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});

it('has no registration, password reset or verification routes', function () {
    $this->get('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/verify-email')->assertNotFound();
});
