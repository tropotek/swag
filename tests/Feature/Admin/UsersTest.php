<?php

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('forbids non-admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.users.store'), [
        'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'password' => 'temporary-pass',
    ])->assertForbidden();

    expect(User::count())->toBe(2);
});

it('lists users', function () {
    User::factory()->create(['name' => 'Bob Example']);

    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSee('Bob Example');
});

it('creates a user with a temporary password', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Bob', 'email' => ' Bob@Example.com', 'password' => 'temporary-pass',
    ])->assertRedirect(route('admin.users.index'));

    $bob = User::where('email', 'bob@example.com')->sole();
    expect($bob->must_change_password)->toBeTrue()
        ->and($bob->is_admin)->toBeFalse()
        ->and(Hash::check('temporary-pass', $bob->password))->toBeTrue();
});

it('can create another admin', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Cat', 'email' => 'cat@example.com', 'password' => 'temporary-pass', 'is_admin' => '1',
    ]);

    expect(User::where('email', 'cat@example.com')->sole()->is_admin)->toBeTrue();
});

it('rejects a duplicate email regardless of case', function () {
    User::factory()->create(['email' => 'bob@example.com']);

    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Bob', 'email' => 'BOB@example.com', 'password' => 'temporary-pass',
    ])->assertSessionHasErrors('email');
});

it('rejects a short temporary password', function () {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'short',
    ])->assertSessionHasErrors('password');
});

it('sets a new temporary password', function () {
    $bob = User::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.users.password.update', $bob), ['password' => 'another-temp-pass'])
        ->assertRedirect(route('admin.users.index'));

    $bob->refresh();
    expect($bob->must_change_password)->toBeTrue()
        ->and(Hash::check('another-temp-pass', $bob->password))->toBeTrue();
});

it('deletes a user with their pages and tokens', function () {
    $bob = User::factory()->create();
    Page::factory()->for($bob)->count(2)->create();
    $bob->createToken('claude');

    $this->actingAs($this->admin)->delete(route('admin.users.destroy', $bob))->assertRedirect(route('admin.users.index'));

    expect(User::find($bob->id))->toBeNull()
        ->and(Page::count())->toBe(0)
        ->and(PersonalAccessToken::count())->toBe(0);
});

it('will not let an admin delete themselves', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->delete(route('admin.users.destroy', $this->admin))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('error');

    expect($this->admin->fresh())->not->toBeNull();
});

it('does not give admins access to other users\' pages', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)->get(route('pages.show', $page))->assertNotFound();
});

it('shows the Users nav link only to admins', function () {
    $this->actingAs($this->admin)->get('/')->assertSee(route('admin.users.index'), false);
    $this->actingAs(User::factory()->create())->get('/')->assertDontSee(route('admin.users.index'), false);
});
