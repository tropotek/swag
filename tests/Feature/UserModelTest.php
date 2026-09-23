<?php

use App\Models\User;

it('stores emails lowercased and trimmed', function () {
    $user = User::factory()->create(['email' => '  Ada@Example.COM ']);

    expect($user->fresh()->email)->toBe('ada@example.com');
});

it('does not mass-assign the admin flags', function () {
    $user = new User(['name' => 'x', 'is_admin' => true, 'must_change_password' => true]);

    expect($user->is_admin)->toBeFalse()
        ->and($user->must_change_password)->toBeFalse();
});

it('has admin and must-change-password factory states', function () {
    expect(User::factory()->admin()->create()->is_admin)->toBeTrue()
        ->and(User::factory()->mustChangePassword()->create()->must_change_password)->toBeTrue();
});
