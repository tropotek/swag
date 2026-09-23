<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an admin with a lowercased email', function () {
    $this->artisan('noteboard:create-admin')
        ->expectsQuestion('Name', 'Ada')
        ->expectsQuestion('Email', '  Ada@Example.COM ')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->assertSuccessful();

    $user = User::sole();
    expect($user->email)->toBe('ada@example.com')
        ->and($user->is_admin)->toBeTrue()
        ->and($user->must_change_password)->toBeFalse()
        ->and(Hash::check('correct-horse-battery', $user->password))->toBeTrue();
});

it('refuses when an admin already exists', function () {
    User::factory()->admin()->create();

    $this->artisan('noteboard:create-admin')->assertFailed();

    expect(User::count())->toBe(1);
});

it('creates another admin with --force', function () {
    User::factory()->admin()->create();

    $this->artisan('noteboard:create-admin', ['--force' => true])
        ->expectsQuestion('Name', 'Bob')
        ->expectsQuestion('Email', 'bob@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->assertSuccessful();

    expect(User::where('is_admin', true)->count())->toBe(2);
});

it('rejects an invalid email and a short password', function () {
    $this->artisan('noteboard:create-admin')
        ->expectsQuestion('Name', 'Ada')
        ->expectsQuestion('Email', 'not-an-email')
        ->expectsQuestion('Password', 'short')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('rejects an email that differs only in case from an existing user', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->artisan('noteboard:create-admin')
        ->expectsQuestion('Name', 'Ada')
        ->expectsQuestion('Email', 'ADA@example.com')
        ->expectsQuestion('Password', 'correct-horse-battery')
        ->assertFailed();

    expect(User::count())->toBe(1);
});
