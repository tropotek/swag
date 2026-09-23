<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('forces a user with a temporary password to the change form', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)->get('/')->assertRedirect(route('account.password.edit'));
    $this->actingAs($user)->get(route('account.edit'))->assertRedirect(route('account.password.edit'));
    $this->actingAs($user)->get(route('account.password.edit'))->assertOk()->assertSee('Choose a new one to continue');
});

it('still lets a forced user log out', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/login');
});

it('clears the flag after a password change', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => 'temporary-pass']);

    $this->actingAs($user)->put(route('account.password.update'), [
        'current_password' => 'temporary-pass',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertRedirect(route('home'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('brand-new-password', $user->password))->toBeTrue();

    $this->actingAs($user)->get('/')->assertOk();
});

it('requires the current password', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => 'temporary-pass']);

    $this->actingAs($user)->put(route('account.password.update'), [
        'current_password' => 'wrong',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertSessionHasErrors('current_password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('rejects reusing the temporary password', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => 'temporary-pass']);

    $this->actingAs($user)->put(route('account.password.update'), [
        'current_password' => 'temporary-pass',
        'password' => 'temporary-pass',
        'password_confirmation' => 'temporary-pass',
    ])->assertSessionHasErrors('password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('rejects a mismatched confirmation', function () {
    $user = User::factory()->create(['password' => 'old-password-1']);

    $this->actingAs($user)->put(route('account.password.update'), [
        'current_password' => 'old-password-1',
        'password' => 'brand-new-password',
        'password_confirmation' => 'something-else',
    ])->assertSessionHasErrors('password');
});
